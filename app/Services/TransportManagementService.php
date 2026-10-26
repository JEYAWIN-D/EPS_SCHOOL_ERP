<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TransportAllotment;
use App\Models\TransportAssignmentHistory;
use App\Models\TransportImportLog;
use App\Models\TransportRosterPassenger;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TransportManagementService
{
    /**
     * Backup all transport data to a persistent JSON snapshot.
     */
    public function backupTransportData(): string
    {
        $backupDir = storage_path('app/backups/transport');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filePath = $backupDir . "/transport_backup_{$timestamp}.json";

        $data = [
            'created_at'        => now()->toDateTimeString(),
            'vehicles'          => Vehicle::all()->toArray(),
            'routes'            => TransportRoute::all()->toArray(),
            'stops'             => TransportStop::all()->toArray(),
            'allotments'        => TransportAllotment::all()->toArray(),
            'roster_passengers' => TransportRosterPassenger::all()->toArray(),
        ];

        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
        Log::info("Transport data backed up to: {$filePath}");

        return $filePath;
    }

    /**
     * Safely deactivate or retire an old route without breaking historical assignments.
     */
    public function retireRoute(int $routeId, ?int $reassignToRouteId = null, string $reason = ''): array
    {
        $route = TransportRoute::findOrFail($routeId);
        $activeAllotments = TransportAllotment::where('route_id', $routeId)->where('is_active', true)->get();
        $assignedCount = $activeAllotments->count();

        return DB::transaction(function () use ($route, $reassignToRouteId, $activeAllotments, $assignedCount, $reason) {
            $userId = Auth::id();
            $reassigned = 0;

            if ($reassignToRouteId && $reassignToRouteId !== $route->id) {
                $targetRoute = TransportRoute::findOrFail($reassignToRouteId);
                $firstTargetStop = TransportStop::where('route_id', $targetRoute->id)->where('is_active', true)->orderBy('stop_order')->first();

                foreach ($activeAllotments as $allotment) {
                    $oldRouteId = $allotment->route_id;
                    $oldVehicleId = $allotment->vehicle_id;
                    $oldStopId = $allotment->stop_id;

                    // Try matching stop by name in target route
                    $matchedStop = null;
                    if ($allotment->stop) {
                        $matchedStop = TransportStop::where('route_id', $targetRoute->id)
                            ->where('name', 'like', $allotment->stop->name)
                            ->first();
                    }
                    $targetStop = $matchedStop ?: $firstTargetStop;

                    $allotment->update([
                        'route_id'   => $targetRoute->id,
                        'vehicle_id' => $targetRoute->vehicle_id,
                        'stop_id'    => $targetStop?->id,
                        'remarks'    => trim(($allotment->remarks ?? '') . " | Reassigned from Route #{$route->id} ({$reason})"),
                    ]);

                    TransportAssignmentHistory::create([
                        'student_id'       => $allotment->student_id,
                        'academic_year_id' => $allotment->academic_year_id,
                        'old_vehicle_id'   => $oldVehicleId,
                        'new_vehicle_id'   => $targetRoute->vehicle_id,
                        'old_route_id'     => $oldRouteId,
                        'new_route_id'     => $targetRoute->id,
                        'old_stop_id'      => $oldStopId,
                        'new_stop_id'      => $targetStop?->id,
                        'change_type'      => 'migrated',
                        'effective_date'   => today(),
                        'remarks'          => "Route retired: {$route->route_name} -> {$targetRoute->route_name}. {$reason}",
                        'changed_by'       => $userId,
                    ]);

                    $reassigned++;
                }
            }

            // If still has allotments and not reassigned, safely deactivate rather than delete
            if ($assignedCount > 0 && !$reassignToRouteId) {
                $route->update(['is_active' => false]);
                return [
                    'success' => true,
                    'action'  => 'deactivated',
                    'message' => "Route \"{$route->route_name}\" has been safely deactivated. All {$assignedCount} student history records remain preserved.",
                    'reassigned_count' => 0,
                ];
            }

            // Safe to delete if 0 assigned
            if (TransportAllotment::where('route_id', $route->id)->where('is_active', true)->count() === 0) {
                // Unlink vehicle and deactivate stops
                Vehicle::where('route_id', $route->id)->update(['route_id' => null]);
                TransportStop::where('route_id', $route->id)->update(['is_active' => false]);
                $route->update(['is_active' => false]);

                return [
                    'success' => true,
                    'action'  => 'retired',
                    'message' => "Route \"{$route->route_name}\" successfully retired. {$reassigned} students migrated to new route.",
                    'reassigned_count' => $reassigned,
                ];
            }

            return [
                'success' => false,
                'action'  => 'error',
                'message' => "Could not complete route action.",
                'reassigned_count' => 0,
            ];
        });
    }

    /**
     * Safely delete or deactivate a bus stop.
     */
    public function safeDeleteStop(int $stopId, ?int $reassignToStopId = null): array
    {
        $stop = TransportStop::findOrFail($stopId);
        $activeAllotments = TransportAllotment::where('stop_id', $stopId)->where('is_active', true)->get();
        $count = $activeAllotments->count();

        if ($count > 0 && !$reassignToStopId) {
            // Deactivate instead of hard deleting
            $stop->update(['is_active' => false]);
            return [
                'success' => true,
                'action'  => 'deactivated',
                'message' => "Bus stop \"{$stop->name}\" is assigned to {$count} active students. It has been deactivated safely.",
            ];
        }

        return DB::transaction(function () use ($stop, $activeAllotments, $reassignToStopId) {
            $userId = Auth::id();
            if ($reassignToStopId) {
                $targetStop = TransportStop::findOrFail($reassignToStopId);
                foreach ($activeAllotments as $a) {
                    $oldStopId = $a->stop_id;
                    $a->update([
                        'stop_id'     => $targetStop->id,
                        'pickup_stop' => $targetStop->name,
                    ]);

                    TransportAssignmentHistory::create([
                        'student_id'       => $a->student_id,
                        'academic_year_id' => $a->academic_year_id,
                        'old_vehicle_id'   => $a->vehicle_id,
                        'new_vehicle_id'   => $a->vehicle_id,
                        'old_route_id'     => $a->route_id,
                        'new_route_id'     => $a->route_id,
                        'old_stop_id'      => $oldStopId,
                        'new_stop_id'      => $targetStop->id,
                        'change_type'      => 'changed',
                        'effective_date'   => today(),
                        'remarks'          => "Stop {$stop->name} removed; students reassigned to {$targetStop->name}",
                        'changed_by'       => $userId,
                    ]);
                }
            }

            $stop->delete();
            return [
                'success' => true,
                'action'  => 'deleted',
                'message' => "Bus stop \"{$stop->name}\" deleted successfully.",
            ];
        });
    }

    /**
     * Allocate or update student transport with timing override support.
     */
    public function assignStudentTransport(int $studentId, array $data, ?int $userId = null): TransportAllotment
    {
        $student = Student::findOrFail($studentId);
        $userId  = $userId ?: Auth::id();

        $routeId     = $data['route_id'] ?? null;
        $stopId      = $data['stop_id'] ?? null;
        $dropStopId  = $data['drop_stop_id'] ?? $stopId;
        $vehicleId   = $data['vehicle_id'] ?? null;
        $pickupTime  = $data['pickup_time'] ?? null;
        $dropTime    = $data['drop_time'] ?? null;
        $fee         = isset($data['fee']) ? (float)$data['fee'] : null;
        $remarks     = $data['remarks'] ?? null;
        $startDate   = $data['start_date'] ?? today()->toDateString();

        $route = $routeId ? TransportRoute::find($routeId) : null;
        $stop  = $stopId ? TransportStop::find($stopId) : null;
        $dropStop = $dropStopId ? TransportStop::find($dropStopId) : $stop;

        // Auto determine vehicle if not provided
        if (!$vehicleId && $route && $route->vehicle_id) {
            $vehicleId = $route->vehicle_id;
        } elseif (!$vehicleId && $stop && $stop->vehicle_id) {
            $vehicleId = $stop->vehicle_id;
        }

        // Auto populate timings from stop if not given
        $pickupOverride = false;
        if (!empty($pickupTime)) {
            $pickupOverride = ($stop && $pickupTime !== $stop->pickup_time);
        } else {
            $pickupTime = $stop?->pickup_time;
        }

        $dropOverride = false;
        if (!empty($dropTime)) {
            $dropOverride = ($dropStop && $dropTime !== $dropStop->drop_time);
        } else {
            $dropTime = $dropStop?->drop_time ?? $stop?->drop_time;
        }

        // Auto populate fee from stop / route if not given
        if ($fee === null) {
            $fee = (float)($stop?->fare ?: ($route?->fee ?: 0));
        }

        $currentYear = AcademicYear::current() ?? AcademicYear::latest('id')->first();
        $enrollment  = StudentEnrollment::where('student_id', $studentId)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->first();

        return DB::transaction(function () use (
            $student, $route, $stop, $dropStop, $vehicleId, $pickupTime, $dropTime,
            $pickupOverride, $dropOverride, $fee, $remarks, $startDate, $currentYear,
            $enrollment, $userId
        ) {
            // Existing active allotment
            $existing = TransportAllotment::where('student_id', $student->id)->where('is_active', true)->first();

            $oldVehicleId   = $existing?->vehicle_id;
            $oldRouteId     = $existing?->route_id;
            $oldStopId      = $existing?->stop_id;
            $oldPickupTime  = $existing?->pickup_time;
            $oldDropTime    = $existing?->drop_time;
            $oldFee         = $existing?->fee;

            if ($existing) {
                $existing->update([
                    'route_id'             => $route?->id,
                    'stop_id'              => $stop?->id,
                    'drop_stop_id'         => $dropStop?->id,
                    'vehicle_id'           => $vehicleId,
                    'fee'                  => $fee,
                    'pickup_time'          => $pickupTime,
                    'drop_time'            => $dropTime,
                    'pickup_time_override' => $pickupOverride,
                    'drop_time_override'   => $dropOverride,
                    'pickup_stop'          => $stop?->name,
                    'drop_stop'            => $dropStop?->name,
                    'is_active'            => true,
                    'status'               => 'active',
                    'start_date'           => $startDate,
                    'remarks'              => $remarks,
                    'academic_year_id'     => $currentYear?->id,
                    'enrollment_id'        => $enrollment?->id,
                ]);
                $allotment = $existing;
                $changeType = 'changed';
            } else {
                $allotment = TransportAllotment::create([
                    'student_id'           => $student->id,
                    'enrollment_id'        => $enrollment?->id,
                    'route_id'             => $route?->id,
                    'stop_id'              => $stop?->id,
                    'drop_stop_id'         => $dropStop?->id,
                    'vehicle_id'           => $vehicleId,
                    'fee'                  => $fee,
                    'pickup_time'          => $pickupTime,
                    'drop_time'            => $dropTime,
                    'pickup_time_override' => $pickupOverride,
                    'drop_time_override'   => $dropOverride,
                    'boarding_stop'        => $stop?->name,
                    'pickup_stop'          => $stop?->name,
                    'drop_stop'            => $dropStop?->name,
                    'is_active'            => true,
                    'status'               => 'active',
                    'start_date'           => $startDate,
                    'remarks'              => $remarks,
                    'academic_year_id'     => $currentYear?->id,
                ]);
                $changeType = 'assigned';
            }

            // Sync student model columns
            $student->update([
                'transport_route_id'    => $route?->id,
                'transport_stop_id'     => $stop?->id,
                'transport_fee'         => $fee,
                'transport_distance_km' => $stop?->distance_km ?? $route?->distance_km ?? 0,
            ]);

            // Audit history
            TransportAssignmentHistory::create([
                'student_id'       => $student->id,
                'academic_year_id' => $currentYear?->id,
                'old_vehicle_id'   => ($oldVehicleId && Vehicle::where('id', $oldVehicleId)->exists()) ? $oldVehicleId : null,
                'new_vehicle_id'   => $vehicleId,
                'old_route_id'     => ($oldRouteId && TransportRoute::where('id', $oldRouteId)->exists()) ? $oldRouteId : null,
                'new_route_id'     => $route?->id,
                'old_stop_id'      => ($oldStopId && TransportStop::where('id', $oldStopId)->exists()) ? $oldStopId : null,
                'new_stop_id'      => $stop?->id,
                'old_pickup_time'  => $oldPickupTime,
                'new_pickup_time'  => $pickupTime,
                'old_drop_time'    => $oldDropTime,
                'new_drop_time'    => $dropTime,
                'old_fee'          => $oldFee,
                'new_fee'          => $fee,
                'change_type'      => $changeType,
                'effective_date'   => $startDate,
                'remarks'          => $remarks,
                'changed_by'       => $userId,
            ]);

            return $allotment;
        });
    }

    /**
     * Discontinue student transport assignment safely.
     */
    public function discontinueStudentTransport(int $studentId, string $reason = 'Requested by parent', ?string $effectiveDate = null, ?int $userId = null): bool
    {
        $student = Student::findOrFail($studentId);
        $userId  = $userId ?: Auth::id();
        $date    = $effectiveDate ?: today()->toDateString();

        $activeAllotment = TransportAllotment::where('student_id', $studentId)->where('is_active', true)->first();
        if (!$activeAllotment) {
            return false;
        }

        DB::transaction(function () use ($student, $activeAllotment, $reason, $date, $userId) {
            $currentYear = AcademicYear::current();

            // Record history before updating
            TransportAssignmentHistory::create([
                'student_id'       => $student->id,
                'academic_year_id' => $currentYear?->id,
                'old_vehicle_id'   => ($activeAllotment->vehicle_id && Vehicle::where('id', $activeAllotment->vehicle_id)->exists()) ? $activeAllotment->vehicle_id : null,
                'new_vehicle_id'   => null,
                'old_route_id'     => ($activeAllotment->route_id && TransportRoute::where('id', $activeAllotment->route_id)->exists()) ? $activeAllotment->route_id : null,
                'new_route_id'     => null,
                'old_stop_id'      => ($activeAllotment->stop_id && TransportStop::where('id', $activeAllotment->stop_id)->exists()) ? $activeAllotment->stop_id : null,
                'new_stop_id'      => null,
                'old_pickup_time'  => $activeAllotment->pickup_time,
                'new_pickup_time'  => null,
                'old_drop_time'    => $activeAllotment->drop_time,
                'new_drop_time'    => null,
                'old_fee'          => $activeAllotment->fee,
                'new_fee'          => 0,
                'change_type'      => 'discontinued',
                'effective_date'   => $date,
                'remarks'          => $reason,
                'changed_by'       => $userId,
            ]);

            $activeAllotment->update([
                'is_active'           => false,
                'status'              => 'discontinued',
                'end_date'            => $date,
                'discontinued_reason' => $reason,
                'discontinued_at'     => now(),
                'discontinued_by'     => $userId,
            ]);

            $student->update([
                'transport_route_id' => null,
                'transport_stop_id'  => null,
                'transport_fee'      => 0,
            ]);
        });

        return true;
    }

    /**
     * Import official Bus Stops from Excel (e.g. TRANSPORT DETAILS.xlsx).
     */
    public function importBusStopsFromExcel(string $filePath, ?int $userId = null, ?string $originalFilename = null): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $this->backupTransportData();

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $maxRow = $sheet->getHighestRow();

        $summary = [
            'total_rows'       => 0,
            'success_count'    => 0,
            'duplicate_count'  => 0,
            'invalid_count'    => 0,
            'failed_count'     => 0,
            'imported_stops'   => [],
            'errors'           => [],
        ];

        // Known default Van vehicles mapping
        $vanVehicleMap = [
            'VAN I'   => 'TN-33-AX-1001',
            'VAN II'  => 'TN-33-AX-1002',
            'VAN III' => 'TN-33-AX-1003',
            'VAN IV'  => 'TN-33-AX-1004',
            'VAN V'   => 'TN-33-AX-1005',
        ];

        $currentVanKey = null;
        $orderCounter  = 1;

        for ($r = 1; $r <= $maxRow; $r++) {
            $colA = trim((string)$sheet->getCell('A' . $r)->getValue());
            $colB = trim((string)$sheet->getCell('B' . $r)->getValue());
            $colC = trim((string)$sheet->getCell('C' . $r)->getValue());
            $colD = trim((string)$sheet->getCell('D' . $r)->getValue());

            // Check if row designates a Van Header, e.g. "VAN I" in col B or col A
            $headerText = strtoupper($colB ?: $colA);
            foreach (array_keys($vanVehicleMap) as $vk) {
                if (stripos($headerText, $vk) !== false) {
                    $currentVanKey = $vk;
                    $orderCounter  = 1;
                    continue 2;
                }
            }

            // Skip title/empty rows
            if (empty($colA) && empty($colB)) continue;
            if (stripos($colA, 'TRANSPORT DETAILS') !== false || stripos($colB, 'KM') !== false || stripos($colC, 'FEES') !== false) {
                continue;
            }

            // Normal data row: Col A = Serial No, Col B = Stop Name, Col C = KM, Col D = Fare
            $stopName = strtoupper(trim($colB));
            $km       = (float) preg_replace('/[^0-9.]/', '', $colC);
            $fare     = (float) preg_replace('/[^0-9.]/', '', $colD);

            if (empty($stopName) && !empty($colA) && !is_numeric($colA)) {
                $stopName = strtoupper(trim($colA));
            }

            if (empty($stopName) || empty($currentVanKey)) {
                if (!empty($stopName) && empty($currentVanKey)) {
                    $summary['invalid_count']++;
                    $summary['errors'][] = "Row {$r}: Stop '{$stopName}' has no associated Van specified.";
                }
                continue;
            }

            $summary['total_rows']++;

            try {
                // Find or create Vehicle
                $vehicleNum = $vanVehicleMap[$currentVanKey] ?? null;
                $vehicle = Vehicle::where('vehicle_number', $vehicleNum)->first();

                // Find route for this van
                $routeNum = 'VAN-' . str_pad((string)(array_search($currentVanKey, array_keys($vanVehicleMap)) + 1), 2, '0', STR_PAD_LEFT);
                $route = TransportRoute::where('route_number', $routeNum)->first();

                if (!$route && $vehicle) {
                    $route = TransportRoute::where('vehicle_id', $vehicle->id)->first();
                }

                if (!$route) {
                    $route = TransportRoute::create([
                        'route_name'    => "{$currentVanKey} Official Route",
                        'route_number'  => $routeNum,
                        'vehicle_id'    => $vehicle?->id,
                        'from_location' => 'School Campus',
                        'to_location'   => 'Main Route Loop',
                        'fee'           => $fare ?: 15000,
                        'is_active'     => true,
                    ]);
                }

                // Match existing stop on this route by sequence order
                $existingStop = TransportStop::where('route_id', $route->id)
                    ->where('stop_order', $orderCounter)
                    ->first();

                if ($existingStop) {
                    $existingStop->update([
                        'name'        => $stopName,
                        'stop_order'  => $orderCounter,
                        'distance_km' => $km ?: $existingStop->distance_km,
                        'fare'        => $fare ?: $existingStop->fare,
                        'vehicle_id'  => $vehicle?->id,
                        'van_number'  => $currentVanKey,
                        'is_active'   => true,
                    ]);
                    $summary['duplicate_count']++;
                    $summary['success_count']++;
                    $summary['imported_stops'][] = [
                        'row'    => $r,
                        'van'    => $currentVanKey,
                        'stop'   => $stopName,
                        'action' => 'updated_existing',
                    ];
                } else {
                    TransportStop::create([
                        'route_id'    => $route->id,
                        'vehicle_id'  => $vehicle?->id,
                        'van_number'  => $currentVanKey,
                        'name'        => $stopName,
                        'stop_order'  => $orderCounter,
                        'distance_km' => $km,
                        'fare'        => $fare,
                        'pickup_time' => '07:30',
                        'drop_time'   => '16:00',
                        'landmark'    => $stopName,
                        'is_active'   => true,
                    ]);
                    $summary['success_count']++;
                    $summary['imported_stops'][] = [
                        'row'    => $r,
                        'van'    => $currentVanKey,
                        'stop'   => $stopName,
                        'action' => 'created',
                    ];
                }
                $orderCounter++;

            } catch (\Throwable $e) {
                $summary['failed_count']++;
                $summary['errors'][] = "Row {$r} ({$stopName}): " . $e->getMessage();
            }
        }

        TransportImportLog::create([
            'file_name'       => $originalFilename ?: basename($filePath),
            'import_type'     => 'stops_master',
            'total_rows'      => $summary['total_rows'],
            'success_count'   => $summary['success_count'],
            'duplicate_count' => $summary['duplicate_count'],
            'invalid_count'   => $summary['invalid_count'],
            'failed_count'    => $summary['failed_count'],
            'summary_json'    => $summary,
            'imported_by'     => $userId ?: Auth::id(),
        ]);

        return $summary;
    }

    /**
     * Import Student Transport Allocations & Timings from Excel (e.g. TRANSPORT FULL DETAILS.xlsx).
     */
    public function importStudentAllocationsFromExcel(string $filePath, ?int $userId = null, ?string $originalFilename = null): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $this->backupTransportData();

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $summary = [
            'total_rows'      => 0,
            'success_count'   => 0,
            'duplicate_count' => 0,
            'invalid_count'   => 0,
            'failed_count'    => 0,
            'students'        => [],
            'errors'          => [],
        ];

        $vanVehicleMap = [
            'VAN I'   => 'TN-33-AX-1001',
            'VAN II'  => 'TN-33-AX-1002',
            'VAN III' => 'TN-33-AX-1003',
            'VAN IV'  => 'TN-33-AX-1004',
            'VAN V'   => 'TN-33-AX-1005',
        ];

        $currentYear = AcademicYear::current() ?? AcademicYear::latest('id')->first();

        // Preload students into memory map for instant lookup (prevents hundreds of remote DB queries)
        $students = Student::select('id', 'first_name', 'last_name', 'admission_no', 'status')->get();
        $studentMap = [];
        foreach ($students as $st) {
            $fullName = strtoupper(trim($st->first_name . ' ' . ($st->last_name ?? '')));
            $firstName = strtoupper(trim($st->first_name));
            if ($fullName !== '') $studentMap[$fullName] = $st;
            if ($firstName !== '' && !isset($studentMap[$firstName])) $studentMap[$firstName] = $st;
        }

        // Preload vehicles, routes, and stops
        $vehicles = Vehicle::whereIn('vehicle_number', array_values($vanVehicleMap))->get()->keyBy('vehicle_number');
        $routes   = TransportRoute::whereIn('vehicle_id', $vehicles->pluck('id')->filter())->with('stops')->get()->keyBy('vehicle_id');

        $stopCache = [];
        foreach ($routes as $rObj) {
            foreach ($rObj->stops as $sObj) {
                $stopCache[$rObj->id . '_' . strtoupper(trim($sObj->name))] = $sObj;
            }
        }

        $allotmentMap = TransportAllotment::where('is_active', true)->get()->keyBy('student_id');
        $enrollmentMap = StudentEnrollment::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->get()->keyBy('student_id');

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $vanKey = strtoupper(trim($sheetName));

            // Normalize VAN I, VAN II etc.
            if (!isset($vanVehicleMap[$vanKey])) {
                // Try finding match
                foreach (array_keys($vanVehicleMap) as $vk) {
                    if (stripos($vanKey, str_replace(' ', '', $vk)) !== false || stripos($vanKey, $vk) !== false) {
                        $vanKey = $vk;
                        break;
                    }
                }
            }

            if (!isset($vanVehicleMap[$vanKey])) {
                continue; // Not a van sheet
            }

            $vehicleNum = $vanVehicleMap[$vanKey];
            $vehicle = $vehicles[$vehicleNum] ?? Vehicle::where('vehicle_number', $vehicleNum)->first();
            $route = $vehicle ? ($routes[$vehicle->id] ?? TransportRoute::where('vehicle_id', $vehicle->id)->first()) : null;

            $maxRow = min($sheet->getHighestRow(), 100); // Guard against infinite empty rows in Excel

            // Locate header columns
            $snoCol = 'A'; $nameCol = 'B'; $classCol = 'C'; $areaCol = 'D'; $amountCol = 'E'; $morningCol = 'F'; $eveningCol = 'G';

            for ($r = 1; $r <= min(5, $maxRow); $r++) {
                foreach (range('A', 'G') as $col) {
                    $headerVal = strtoupper(trim((string)$sheet->getCell($col . $r)->getValue()));
                    if ($headerVal === 'NAME') $nameCol = $col;
                    if ($headerVal === 'CLASS') $classCol = $col;
                    if ($headerVal === 'AREA' || $headerVal === 'ADDRESS') $areaCol = $col;
                    if ($headerVal === 'AMOUNT') $amountCol = $col;
                    if ($headerVal === 'MORNING' || $headerVal === 'TIME') $morningCol = $col;
                    if ($headerVal === 'EVENING') $eveningCol = $col;
                }
            }

            // Process sheet rows inside a single database transaction for maximum performance
            DB::transaction(function () use (
                $sheet, $maxRow, $nameCol, $classCol, $areaCol, $amountCol, $morningCol, $eveningCol,
                $vanKey, $vehicle, $route, &$stopCache, $studentMap, &$allotmentMap, $enrollmentMap,
                $currentYear, $userId, &$summary
            ) {
                for ($r = 3; $r <= $maxRow; $r++) {
                    $name = trim((string)$sheet->getCell($nameCol . $r)->getValue());
                    if (empty($name) || strtoupper($name) === 'NAME') continue;

                    $className   = trim((string)$sheet->getCell($classCol . $r)->getValue());
                    $area        = strtoupper(trim((string)$sheet->getCell($areaCol . $r)->getValue()));
                    $amount      = (float) preg_replace('/[^0-9.]/', '', (string)$sheet->getCell($amountCol . $r)->getValue());
                    $morningTime = trim((string)$sheet->getCell($morningCol . $r)->getValue());
                    $eveningTime = trim((string)$sheet->getCell($eveningCol . $r)->getValue());

                    $summary['total_rows']++;

                    try {
                        // Fast stop lookup among official route stops
                        $stop = null;
                        if (!empty($area) && $route) {
                            $stopKey = $route->id . '_' . $area;
                            if (isset($stopCache[$stopKey])) {
                                $stop = $stopCache[$stopKey];
                            } else {
                                // Try substring / fuzzy match among official stops on this route
                                $cleanArea = strtoupper(preg_replace('/[^A-Z0-9]/', '', $area));
                                foreach ($route->stops as $candidate) {
                                    $candName = strtoupper(preg_replace('/[^A-Z0-9]/', '', $candidate->name));
                                    if (!empty($candName) && (str_contains($cleanArea, $candName) || str_contains($candName, $cleanArea))) {
                                        $stop = $candidate;
                                        break;
                                    }
                                }
                                // Fallback to first official stop on route
                                if (!$stop) {
                                    $stop = $route->stops->first();
                                }
                                if ($stop) {
                                    $stopCache[$stopKey] = $stop;
                                }
                            }
                        }

                        // Fast student lookup in memory
                        $cleanName = strtoupper(trim($name));
                        $student = $studentMap[$cleanName] ?? null;

                        $formattedMorning = $this->formatTimeString($morningTime);
                        $formattedEvening = $this->formatTimeString($eveningTime);

                        if ($student) {
                            $existing = $allotmentMap[$student->id] ?? null;

                            if ($existing) {
                                $existing->update([
                                    'route_id'             => $route?->id,
                                    'stop_id'              => $stop?->id,
                                    'drop_stop_id'         => $stop?->id,
                                    'vehicle_id'           => $vehicle?->id,
                                    'fee'                  => $amount,
                                    'pickup_time'          => $formattedMorning,
                                    'drop_time'            => $formattedEvening,
                                    'pickup_time_override' => !empty($formattedMorning),
                                    'drop_time_override'   => !empty($formattedEvening),
                                    'pickup_stop'          => $area,
                                    'drop_stop'            => $area,
                                    'is_active'            => true,
                                    'status'               => 'active',
                                    'remarks'              => "Imported from {$vanKey}",
                                ]);
                            } else {
                                $newAllot = TransportAllotment::create([
                                    'student_id'           => $student->id,
                                    'enrollment_id'        => $enrollmentMap[$student->id]?->id ?? null,
                                    'route_id'             => $route?->id,
                                    'stop_id'              => $stop?->id,
                                    'drop_stop_id'         => $stop?->id,
                                    'vehicle_id'           => $vehicle?->id,
                                    'fee'                  => $amount,
                                    'pickup_time'          => $formattedMorning,
                                    'drop_time'            => $formattedEvening,
                                    'pickup_time_override' => !empty($formattedMorning),
                                    'drop_time_override'   => !empty($formattedEvening),
                                    'boarding_stop'        => $area,
                                    'pickup_stop'          => $area,
                                    'drop_stop'            => $area,
                                    'is_active'            => true,
                                    'status'               => 'active',
                                    'start_date'           => today()->toDateString(),
                                    'remarks'              => "Imported from {$vanKey}",
                                    'academic_year_id'     => $currentYear?->id,
                                ]);
                                $allotmentMap[$student->id] = $newAllot;
                            }

                            $student->update([
                                'transport_route_id' => $route?->id,
                                'transport_stop_id'  => $stop?->id,
                                'transport_fee'      => $amount,
                            ]);

                            TransportAssignmentHistory::create([
                                'student_id'       => $student->id,
                                'academic_year_id' => $currentYear?->id,
                                'new_vehicle_id'   => $vehicle?->id,
                                'new_route_id'     => $route?->id,
                                'new_stop_id'      => $stop?->id,
                                'new_pickup_time'  => $formattedMorning,
                                'new_drop_time'    => $formattedEvening,
                                'new_fee'          => $amount,
                                'change_type'      => $existing ? 'changed' : 'assigned',
                                'effective_date'   => today()->toDateString(),
                                'remarks'          => "Imported from {$vanKey}",
                                'changed_by'       => $userId,
                            ]);

                            $summary['success_count']++;
                        } else {
                            // Still record in TransportRosterPassenger for full completeness
                            TransportRosterPassenger::updateOrCreate(
                                [
                                    'van_number'   => $vanKey,
                                    'student_name' => $name,
                                ],
                                [
                                    'van_label'    => "{$vanKey} (" . ($vehicle?->registration_number ?? '') . ")",
                                    'route_id'     => $route?->id,
                                    'vehicle_id'   => $vehicle?->id,
                                    'class_name'   => $className,
                                    'area'         => $area,
                                    'amount'       => $amount,
                                    'morning_time' => $morningTime,
                                    'evening_time' => $eveningTime,
                                    'is_active'    => true,
                                ]
                            );
                            $summary['duplicate_count']++;
                            $summary['success_count']++;
                        }

                        $summary['students'][] = [
                            'van'     => $vanKey,
                            'name'    => $name,
                            'class'   => $className,
                            'area'    => $area,
                            'amount'  => $amount,
                            'matched' => $student !== null,
                        ];

                    } catch (\Throwable $e) {
                        $summary['failed_count']++;
                        $summary['errors'][] = "Sheet {$vanKey}, Row {$r} ({$name}): " . $e->getMessage();
                    }
                }
            });
        }

        TransportImportLog::create([
            'file_name'       => $originalFilename ?: basename($filePath),
            'import_type'     => 'student_allocations',
            'total_rows'      => $summary['total_rows'],
            'success_count'   => $summary['success_count'],
            'duplicate_count' => $summary['duplicate_count'],
            'invalid_count'   => $summary['invalid_count'],
            'failed_count'    => $summary['failed_count'],
            'summary_json'    => $summary,
            'imported_by'     => $userId ?: Auth::id(),
        ]);

        return $summary;
    }

    /**
     * Convert Excel time strings like '7.15', '8.4', 'EVE', '7:30' into standard HH:MM:SS.
     */
    private function formatTimeString(?string $val): ?string
    {
        if (empty($val) || strtoupper(trim($val)) === 'EVE' || stripos($val, 'drop') !== false) {
            return null;
        }

        $val = trim($val);
        if (strpos($val, '.') !== false) {
            $parts = explode('.', $val);
            $hr = (int) $parts[0];
            $min = isset($parts[1]) ? (int) str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : 0;
            return sprintf('%02d:%02d:00', $hr, $min);
        }

        if (strpos($val, ':') !== false) {
            $parts = explode(':', $val);
            $hr = (int) $parts[0];
            $min = isset($parts[1]) ? (int) $parts[1] : 0;
            return sprintf('%02d:%02d:00', $hr, $min);
        }

        return null;
    }
}
