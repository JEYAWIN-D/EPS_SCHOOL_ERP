<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\ArrayExport;
use App\Models\BusAttendance;
use App\Models\Classes;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\TransportAllotment;
use App\Models\TransportAttendant;
use App\Models\TransportRosterPassenger;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleFuelLog;
use App\Models\VehicleMaintenance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TransportController extends Controller
{
    public function index()
    {
        $cachedData = Cache::remember('transport_index_stats_v3', 120, function () {
            $rosterStudents = TransportRosterPassenger::where('is_active', true)->count();
            $allotmentStudents = TransportAllotment::where('is_active', true)->count();
            $stats = [
                'routes'   => TransportRoute::where('is_active', true)->count(),
                'vehicles' => Vehicle::where('is_active', true)->count(),
                'students' => max($rosterStudents, $allotmentStudents),
            ];

            try {
                $stats['drivers'] = DB::table('transport_drivers')->count();
            } catch (\Exception $e) {
                $stats['drivers'] = TransportAttendant::count();
            }

            try {
                $stats['bus_present'] = BusAttendance::whereDate('date', today())->where('status', 'present')->count();
            } catch (\Exception $e) {
                $stats['bus_present'] = 0;
            }

            $maintenanceAlerts = collect();
            try {
                $maintenanceAlerts = VehicleMaintenance::with('vehicle')
                    ->where('next_service_date', '<=', today()->addDays(7))
                    ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
                    ->orderBy('next_service_date')
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {}

            $docAlerts = collect();
            try {
                $docAlerts = Vehicle::where('is_active', true)
                    ->where(fn($q) => $q
                        ->where('insurance_expiry', '<=', today()->addDays(30))
                        ->orWhere('fitness_expiry', '<=', today()->addDays(30))
                    )
                    ->select('id', 'vehicle_number', 'insurance_expiry', 'fitness_expiry')
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {}

            return compact('stats', 'maintenanceAlerts', 'docAlerts');
        });

        $stats = $cachedData['stats'];
        $maintenanceAlerts = $cachedData['maintenanceAlerts'];
        $docAlerts = $cachedData['docAlerts'];

        // Live GPRS fleet (real-time)
        $gprsVehicles = collect();
        try {
            $gprsVehicles = Vehicle::where('is_active', true)
                ->where('gps_enabled', true)
                ->with('route')
                ->select('id', 'vehicle_number', 'vehicle_type', 'driver_name', 'driver_phone', 'gps_status', 'current_speed_kmh', 'current_location_name', 'ignition_status', 'battery_level', 'transport_route_id')
                ->get();
        } catch (\Exception $e) {}

        return view('transport.index', compact('stats', 'maintenanceAlerts', 'docAlerts', 'gprsVehicles'));
    }

    public function routes(Request $request)
    {
        $showInactive = $request->boolean('show_inactive');
        $search = $request->query('search');

        $routesQuery = TransportRoute::withCount('allotments')
            ->with(['vehicle', 'stops'])
            ->when($search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('route_name', 'like', "%{$v}%")
                        ->orWhere('route_number', 'like', "%{$v}%")
                        ->orWhere('from_location', 'like', "%{$v}%")
                        ->orWhere('to_location', 'like', "%{$v}%")
                        ->orWhereHas('vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$v}%"));
                });
            })
            ->when(!$showInactive, fn($q) => $q->where('is_active', true));

        $stats = [
            'total_routes'    => TransportRoute::count(),
            'active_routes'   => TransportRoute::where('is_active', true)->count(),
            'assigned_buses'  => TransportRoute::whereNotNull('vehicle_id')->distinct('vehicle_id')->count('vehicle_id'),
            'total_stops'     => \App\Models\TransportStop::count(),
        ];

        $routes = $routesQuery->paginate(20)->withQueryString();
        return view('transport.routes', compact('routes', 'showInactive', 'stats'));
    }

    public function toggleRoute(int $id)
    {
        $route = TransportRoute::findOrFail($id);
        $route->update(['is_active' => !$route->is_active]);
        $label = $route->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Route \"{$route->route_name}\" {$label}.");
    }

    public function vehicles(Request $request)
    {
        $today    = now()->toDateString();
        $vehicles = Vehicle::with([
                'route.stops',
                'documents',
                'allotments',
                'attendants',
                'maintenanceLogs' => fn($q) => $q->latest('service_date')->limit(1)
            ])
            ->when($request->search, function ($q, $v) {
                $q->where('vehicle_number', 'like', "%$v%")
                  ->orWhere('driver_name', 'like', "%$v%")
                  ->orWhere('attendant_name', 'like', "%$v%")
                  ->orWhere('make', 'like', "%$v%")
                  ->orWhere('model', 'like', "%$v%");
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        // Flag vehicles with overdue next service
        foreach ($vehicles as $v) {
            $latest = $v->maintenanceLogs->first();
            $v->next_service_overdue = $latest && $latest->next_service_date && $latest->next_service_date < $today;
            $v->next_service_date    = $latest?->next_service_date;
        }

        return view('transport.vehicles', compact('vehicles'));
    }

    public function drivers()
    {
        $today    = now()->toDateString();
        $in30     = now()->addDays(30)->toDateString();

        $vehicles = Vehicle::whereNotNull('driver_name')->orderBy('driver_name')->get()
            ->each(function ($v) use ($today, $in30) {
                $v->license_expired   = $v->driver_license_expiry && $v->driver_license_expiry < $today;
                $v->license_due_soon  = $v->driver_license_expiry && !$v->license_expired && $v->driver_license_expiry <= $in30;
            });

        $expiredCount  = $vehicles->where('license_expired', true)->count();
        $dueSoonCount  = $vehicles->where('license_due_soon', true)->count();
        $unverified    = $vehicles->whereIn('police_verification_status', ['pending', 'expired'])->count();

        return view('transport.drivers', compact('vehicles', 'expiredCount', 'dueSoonCount', 'unverified'));
    }

    public function updatePoliceVerification(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:pending,verified,expired']);
        Vehicle::findOrFail($id)->update([
            'police_verification_status' => $request->status,
            'police_verification_date'   => $request->verification_date,
            'police_verification_notes'  => $request->notes,
        ]);
        return back()->with('success', 'Police verification status updated.');
    }

    public function incidents(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $routes   = TransportRoute::orderBy('route_name')->get();

        $incidents = DB::table('transport_incidents')
            ->leftJoin('vehicles', 'transport_incidents.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('transport_routes', 'transport_incidents.route_id', '=', 'transport_routes.id')
            ->select('transport_incidents.*', 'vehicles.vehicle_number', 'transport_routes.route_name')
            ->when($request->vehicle_id, fn($q, $v) => $q->where('transport_incidents.vehicle_id', $v))
            ->when($request->incident_type, fn($q, $v) => $q->where('transport_incidents.incident_type', $v))
            ->when($request->status, fn($q, $v) => $q->where('transport_incidents.status', $v))
            ->orderByDesc('transport_incidents.incident_date')
            ->paginate(20)->withQueryString();

        return view('transport.incidents', compact('incidents', 'vehicles', 'routes'));
    }

    public function storeIncident(Request $request)
    {
        $request->validate([
            'incident_date' => 'required|date',
            'incident_type' => 'required|in:accident,breakdown,theft,vandalism,other',
            'description'   => 'required|string|max:1000',
            'severity'      => 'required|in:minor,moderate,major',
        ]);
        DB::table('transport_incidents')->insert([
            'vehicle_id'    => $request->vehicle_id ?: null,
            'route_id'      => $request->route_id ?: null,
            'incident_date' => $request->incident_date,
            'incident_type' => $request->incident_type,
            'location'      => $request->location,
            'description'   => $request->description,
            'severity'      => $request->severity,
            'action_taken'  => $request->action_taken,
            'reported_by'   => $request->reported_by,
            'fir_number'    => $request->fir_number,
            'estimated_loss'=> $request->estimated_loss ?: null,
            'status'        => 'open',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        return back()->with('success', 'Incident logged.');
    }

    public function updateIncidentStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:open,under_review,resolved,closed']);
        DB::table('transport_incidents')->where('id', $id)->update([
            'status'           => $request->status,
            'resolution_notes' => $request->resolution_notes,
            'resolved_date'    => in_array($request->status, ['resolved', 'closed']) ? now()->toDateString() : null,
            'updated_at'       => now(),
        ]);
        return back()->with('success', 'Incident status updated.');
    }

    public function tracking()
    {
        $routes   = TransportRoute::where('is_active', true)->with(['vehicle', 'stops'])->get();
        $vehicles = Vehicle::where('is_active', true)->with(['route.stops'])->orderBy('vehicle_number')->get();
        
        $stats = [
            'total_vehicles' => $vehicles->count(),
            'online_gprs'    => $vehicles->whereIn('gps_status', ['online', 'in_transit'])->count(),
            'in_transit'     => $vehicles->where('gps_status', 'in_transit')->count(),
            'idle_or_parked' => $vehicles->where('gps_status', 'idle')->count(),
        ];

        return view('transport.tracking', compact('routes', 'vehicles', 'stats'));
    }

    public function liveTelemetry()
    {
        $vehicles = Vehicle::where('is_active', true)
            ->with(['route.stops'])
            ->get()
            ->map(function ($v) {
                return [
                    'id'                   => $v->id,
                    'vehicle_number'       => $v->vehicle_number,
                    'vehicle_type'         => ucfirst($v->vehicle_type ?? 'Bus'),
                    'route_name'           => $v->route?->route_name ?? 'Unassigned Route',
                    'route_id'             => $v->route_id,
                    'gps_device_id'        => $v->gps_device_id ?? ('GPRS-' . $v->id),
                    'gps_imei'             => $v->gps_imei ?? ('864028042' . str_pad($v->id, 6, '0', STR_PAD_LEFT)),
                    'gps_status'           => $v->gps_status ?? 'online',
                    'latitude'             => (float) ($v->current_latitude ?? 13.0827),
                    'longitude'            => (float) ($v->current_longitude ?? 80.2707),
                    'location_name'        => $v->current_location_name ?? 'Chennai School Campus',
                    'speed_kmh'            => (int) ($v->current_speed_kmh ?? 0),
                    'battery_level'        => (int) ($v->battery_level ?? 95),
                    'ignition_status'      => $v->ignition_status ?? 'on',
                    'driver_name'          => $v->driver_name ?: 'Driver Not Assigned',
                    'driver_mobile'        => $v->driver_mobile ?: '—',
                    'last_ping'            => $v->last_gps_ping ? $v->last_gps_ping->diffForHumans() : 'Just now',
                    'capacity'             => $v->seating_capacity ?? 40,
                    'stops'                => ($v->route && $v->route->stops) ? $v->route->stops->map(fn($s) => [
                        'name'        => $s->name,
                        'order'       => $s->stop_order,
                        'distance_km' => $s->distance_km,
                        'landmark'    => $s->landmark,
                    ])->values()->all() : [],
                ];
            });

        return response()->json([
            'success'   => true,
            'timestamp' => now()->toIso8601String(),
            'vehicles'  => $vehicles,
        ]);
    }

    public function showVehicle(int $id)
    {
        $vehicle = Vehicle::with([
            'route.stops' => fn($q) => $q->orderBy('stop_order'),
            'documents.uploader',
            'allotments.student',
            'allotments.route',
            'maintenanceLogs' => fn($q) => $q->latest('service_date')->limit(5),
            'fuelLogs' => fn($q) => $q->latest('log_date')->limit(5),
        ])->findOrFail($id);

        $routes = TransportRoute::where('is_active', true)->orderBy('route_name')->get();

        return view('transport.vehicle-show', compact('vehicle', 'routes'));
    }

    public function editVehicle(int $id)
    {
        $vehicle = Vehicle::with(['route', 'documents'])->findOrFail($id);
        $routes  = TransportRoute::where('is_active', true)->orderBy('route_name')->get();
        return view('transport.vehicle-edit', compact('vehicle', 'routes'));
    }

    public function updateVehicle(Request $request, int $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $validated = $request->validate([
            'vehicle_number'        => 'required|string|max:30|unique:vehicles,vehicle_number,' . $id,
            'registration_number'   => 'nullable|string|max:30',
            'make'                  => 'nullable|string|max:60',
            'model'                 => 'nullable|string|max:60',
            'seating_capacity'      => 'required|integer|min:1',
            'vehicle_type'          => 'nullable|string',
            'driver_name'           => 'nullable|string|max:100',
            'driver_mobile'         => 'nullable|string|max:20',
            'driver_license'        => 'nullable|string|max:50',
            'driver_license_expiry' => 'nullable|date',
            'driver_address'        => 'nullable|string',
            'attendant_name'        => 'nullable|string|max:100',
            'attendant_mobile'      => 'nullable|string|max:20',
            'attendant_address'     => 'nullable|string',
            'attendant_aadhaar'     => 'nullable|string|max:20',
            'route_id'              => 'nullable|exists:transport_routes,id',
            'fitness_expiry'        => 'nullable|date',
            'insurance_expiry'      => 'nullable|date',
            'permit_expiry'         => 'nullable|date',
            'puc_expiry'            => 'nullable|date',
            'tax_expiry'            => 'nullable|date',
            'is_active'             => 'nullable|boolean',
            'gps_enabled'           => 'nullable|boolean',
        ]);

        $validated['capacity'] = $validated['seating_capacity'];
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $vehicle->update($validated);

        // Sync route relation
        if ($vehicle->route_id) {
            TransportRoute::where('id', $vehicle->route_id)->update(['vehicle_id' => $vehicle->id]);
        }

        // Sync attendant
        if (!empty($validated['attendant_name'])) {
            TransportAttendant::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                [
                    'name'      => $validated['attendant_name'],
                    'mobile'    => $validated['attendant_mobile'] ?? null,
                    'aadhaar'   => $validated['attendant_aadhaar'] ?? null,
                    'route_id'  => $vehicle->route_id,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('transport.vehicle.show', $vehicle->id)->with('success', "Vehicle {$vehicle->vehicle_number} updated successfully.");
    }

    public function destroyVehicle(int $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        
        // Check if students are allotted
        $assignedCount = TransportAllotment::where('vehicle_id', $id)->where('is_active', true)->count();
        if ($assignedCount > 0) {
            return back()->withErrors(['error' => "Cannot delete vehicle {$vehicle->vehicle_number} because {$assignedCount} active students are allocated to it. Reassign students first."]);
        }

        // Unlink route
        TransportRoute::where('vehicle_id', $id)->update(['vehicle_id' => null]);
        TransportStop::where('vehicle_id', $id)->update(['vehicle_id' => null]);
        TransportAttendant::where('vehicle_id', $id)->delete();

        // Delete documents
        foreach ($vehicle->documents as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
            $doc->delete();
        }

        $vehicle->delete();
        return redirect()->route('transport.vehicles')->with('success', "Vehicle {$vehicle->vehicle_number} deleted successfully.");
    }

    public function destroyRoute(int $id)
    {
        $route = TransportRoute::findOrFail($id);

        $assignedCount = TransportAllotment::where('route_id', $id)->where('is_active', true)->count();
        if ($assignedCount > 0) {
            return back()->withErrors(['error' => "Cannot delete route {$route->route_name} because {$assignedCount} active students are assigned to it. Reassign students first."]);
        }

        // Unlink vehicles
        Vehicle::where('route_id', $id)->update(['route_id' => null]);
        TransportStop::where('route_id', $id)->delete();
        $route->delete();

        return redirect()->route('transport.routes')->with('success', "Route {$route->route_name} deleted successfully.");
    }

    public function uploadDocument(Request $request, int $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'title'         => 'required|string|max:150',
            'document_type' => 'required|in:rc,insurance,fitness,permit,puc,tax,driver_license,attendant_id,other',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
            'issue_date'    => 'nullable|date',
            'expiry_date'   => 'nullable|date',
            'notes'         => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs("vehicle_documents/{$vehicle->id}", $filename, 'public');

        $doc = VehicleDocument::create([
            'vehicle_id'    => $vehicle->id,
            'title'         => $request->title,
            'document_type' => $request->document_type,
            'file_path'     => $path,
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'issue_date'    => $request->issue_date,
            'expiry_date'   => $request->expiry_date,
            'notes'         => $request->notes,
            'uploaded_by'   => Auth::id(),
        ]);

        // Auto-update vehicle document expiry if matched
        if ($request->expiry_date) {
            $fieldMap = [
                'rc'             => 'rc_expiry',
                'insurance'      => 'insurance_expiry',
                'fitness'        => 'fitness_expiry',
                'permit'         => 'permit_expiry',
                'puc'            => 'puc_expiry',
                'tax'            => 'tax_expiry',
                'driver_license' => 'driver_license_expiry',
            ];
            if (isset($fieldMap[$request->document_type])) {
                $vehicle->update([$fieldMap[$request->document_type] => $request->expiry_date]);
            }
        }

        return back()->with('success', "Document \"{$doc->title}\" uploaded successfully.");
    }

    public function viewDocument(int $id)
    {
        $doc = VehicleDocument::findOrFail($id);
        if (!Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'Document file not found.');
        }

        $path = Storage::disk('public')->path($doc->file_path);
        return response()->file($path);
    }

    public function downloadDocument(int $id)
    {
        $doc = VehicleDocument::findOrFail($id);
        if (!Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'Document file not found.');
        }

        $path = Storage::disk('public')->path($doc->file_path);
        return response()->download($path, $doc->file_name ?? basename($doc->file_path));
    }

    public function destroyDocument(int $id)
    {
        $doc = VehicleDocument::findOrFail($id);
        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $title = $doc->title;
        $doc->delete();

        return back()->with('success', "Document \"{$title}\" deleted.");
    }

    public function allotment(Request $request)
    {
        $routes   = TransportRoute::where('is_active', true)->with('vehicle')->get();
        $stops    = TransportStop::with(['vehicle', 'route'])->orderBy('route_id')->orderBy('stop_order')->get();
        $classes  = Classes::active()->get();
        $sections = Section::all();
        $currentYear = \App\Models\AcademicYear::current();
        $enrollments = StudentEnrollment::with('student', 'class')->where('status', 'active')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get();
        $allotments = TransportAllotment::with(['enrollment.student', 'enrollment.class', 'route.vehicle', 'stop.vehicle'])
            ->where('is_active', true)
            ->when($request->route_id, fn($q, $v) => $q->where('route_id', $v))
            ->when($request->class_id, fn($q, $v) => $q->whereHas('enrollment', fn($q) => $q->where('class_id', $v)))
            ->paginate(25)->withQueryString();
        return view('transport.allotment', compact('routes', 'stops', 'classes', 'sections', 'enrollments', 'allotments'));
    }

    public function storeAllotment(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:student_enrollments,id',
            'route_id'      => 'required|exists:transport_routes,id',
            'stop_id'       => 'nullable|exists:transport_stops,id',
        ]);

        // Seat availability check
        $route = TransportRoute::findOrFail($request->route_id);
        $vehicle = $route->vehicle;
        if ($vehicle) {
            $occupied = TransportAllotment::where('route_id', $route->id)->where('is_active', true)->count();
            $capacity = $vehicle->capacity ?: $vehicle->seating_capacity;
            if ($capacity > 0 && $occupied >= $capacity) {
                return back()->withErrors(['route_id' => "Route \"{$route->route_name}\" is full ({$capacity} seats occupied). Choose another route."])->withInput();
            }
        }

        $stopFare = null;
        $stopDistance = null;
        if ($request->stop_id) {
            $stop = TransportStop::find($request->stop_id);
            $stopFare = $stop?->fare;
            $stopDistance = $stop?->distance_km;
        }

        TransportAllotment::updateOrCreate(
            ['enrollment_id' => $request->enrollment_id],
            [
                'route_id'   => $request->route_id,
                'stop_id'    => $request->stop_id,
                'vehicle_id' => $vehicle?->id,
                'fee'        => $stopFare,
                'is_active'  => true,
            ]
        );

        // Sync with Student Profile
        $enrollment = StudentEnrollment::find($request->enrollment_id);
        if ($enrollment && $enrollment->student) {
            $enrollment->student->update([
                'transport_route_id'    => $request->route_id,
                'transport_stop_id'     => $request->stop_id,
                'transport_distance_km' => $stopDistance,
                'transport_fee'         => $stopFare ?? 0,
            ]);
        }

        // Auto-link transport fee to student via StudentCustomFee if applicable
        if ($stopFare && $stopFare > 0 && $enrollment) {
            $feeHead = \App\Models\FeeHead::firstOrCreate(
                ['name' => 'Transport Fee'],
                ['fee_type' => 'transport', 'is_active' => true]
            );
            $currentYear = \App\Models\AcademicYear::current();
            \App\Models\StudentCustomFee::updateOrCreate(
                [
                    'student_id'       => $enrollment->student_id,
                    'fee_head_id'      => $feeHead->id,
                    'academic_year_id' => $currentYear?->id,
                ],
                [
                    'custom_amount' => $stopFare,
                    'reason'        => 'Auto-linked from transport stop assignment',
                    'set_by'        => \Illuminate\Support\Facades\Auth::id(),
                ]
            );
        }

        return back()->with('success', 'Student allocated to route successfully' . ($stopFare ? " (Transport fee ₹{$stopFare} auto-linked)." : '.'));
    }

    public function deleteAllotment(int $id)
    {
        TransportAllotment::findOrFail($id)->update(['is_active' => false]);
        return back()->with('success', 'Allotment removed.');
    }

    public function exportAllotment()
    {
        return redirect()->route('transport.allotment');
    }

    public function vehicleDocuments()
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get()->each(function ($v) {
            $v->expiring_soon = collect(['fitness_expiry', 'insurance_expiry', 'permit_expiry', 'puc_expiry', 'tax_expiry'])
                ->contains(fn($f) => $v->$f && \Carbon\Carbon::parse($v->$f)->diffInDays(now()) < 30);
        });
        return view('transport.documents', compact('vehicles'));
    }

    public function maintenance(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $logs = VehicleMaintenance::with('vehicle')
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->month, function ($q, $v) {
                [$yr, $mo] = explode('-', $v);
                return $q->whereYear('service_date', $yr)->whereMonth('service_date', $mo);
            })
            ->latest('service_date')->paginate(20)->withQueryString();

        return view('transport.maintenance', compact('vehicles', 'logs'));
    }

    public function storeMaintenance(Request $request)
    {
        $request->validate(['vehicle_id' => 'required|exists:vehicles,id', 'maintenance_type' => 'required|string', 'service_date' => 'required|date']);
        VehicleMaintenance::create($request->only(['vehicle_id', 'maintenance_type', 'service_date', 'odometer', 'cost', 'vendor', 'description', 'next_service_date']));
        return back()->with('success', 'Maintenance log added.');
    }

    public function fuel(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $summary  = null;
        if ($request->filled('month')) {
            [$yr, $mo] = explode('-', $request->month);
            $q = VehicleFuelLog::whereYear('log_date', $yr)->whereMonth('log_date', $mo)
                ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v));
            $totalLitres = $q->sum('quantity_litres');
            $totalAmount = $q->sum('total_cost');
            $maxOdometer = $q->max('odometer_reading');
            $minOdometer = $q->min('odometer_reading');
            $kmDriven    = ($maxOdometer && $minOdometer && $maxOdometer > $minOdometer) ? ($maxOdometer - $minOdometer) : 0;
            $summary = [
                'litres'        => $totalLitres,
                'amount'        => $totalAmount,
                'km_driven'     => $kmDriven,
                'cost_per_km'   => ($kmDriven > 0 && $totalAmount > 0) ? round($totalAmount / $kmDriven, 2) : null,
                'mileage'       => ($totalLitres > 0 && $kmDriven > 0) ? round($kmDriven / $totalLitres, 2) : null,
            ];
        }
        $logs = VehicleFuelLog::with('vehicle')
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->month, function ($q, $v) {
                [$yr, $mo] = explode('-', $v);
                return $q->whereYear('log_date', $yr)->whereMonth('log_date', $mo);
            })
            ->latest('log_date')->paginate(20)->withQueryString();
        return view('transport.fuel', compact('vehicles', 'logs', 'summary'));
    }

    public function storeFuel(Request $request)
    {
        $request->validate(['vehicle_id' => 'required|exists:vehicles,id', 'fill_date' => 'required|date', 'litres' => 'required|numeric|min:0']);
        VehicleFuelLog::create([
            'vehicle_id'       => $request->vehicle_id,
            'log_date'         => $request->fill_date,
            'quantity_litres'  => $request->litres,
            'cost_per_litre'   => $request->rate_per_litre,
            'total_cost'       => $request->amount,
            'odometer_reading' => $request->odometer,
            'filled_by'        => $request->station ?? 'Driver',
        ]);
        return back()->with('success', 'Fuel log added.');
    }

    public function roster(Request $request)
    {
        $selectedVan = $request->get('van', 'all');
        $search = trim((string)$request->get('search', ''));
        $tripFilter = $request->get('trip', 'all');

        $vansList = [
            'VAN I'   => [
                'name'       => 'Van I',
                'route_no'   => 'VAN-01',
                'reg'        => 'TN33AX1001',
                'route_name' => 'RN Pudur / Bhavani / Thindal',
                'color'      => 'blue',
            ],
            'VAN II'  => [
                'name'       => 'Van II',
                'route_no'   => 'VAN-02',
                'reg'        => 'TN33AX1002',
                'route_name' => 'Moolapalayam / Periyar Nagar / Surampatti',
                'color'      => 'emerald',
            ],
            'VAN III' => [
                'name'       => 'Van III',
                'route_no'   => 'VAN-03',
                'reg'        => 'TN33AX1003',
                'route_name' => 'Soolai / EPB Nagar / Mokkiayanpalayam',
                'color'      => 'amber',
            ],
            'VAN IV'  => [
                'name'       => 'Van IV',
                'route_no'   => 'VAN-04',
                'reg'        => 'TN33AX1004',
                'route_name' => 'Karungalpalayam / Thirunagar Colony',
                'color'      => 'purple',
            ],
            'VAN V'   => [
                'name'       => 'Van V',
                'route_no'   => 'VAN-05',
                'reg'        => 'TN33AX1005',
                'route_name' => 'Junior & KG Special Route',
                'color'      => 'rose',
            ],
        ];

        // Overall KPIs
        $totalPassengers = TransportRosterPassenger::where('is_active', true)->count();
        $totalBilling = TransportRosterPassenger::where('is_active', true)->sum('amount');
        $dropOnlyCount = TransportRosterPassenger::where('is_active', true)
            ->whereIn(DB::raw('LOWER(morning_time)'), ['eve', 'evening', '-', ''])
            ->count();
        $bothTripsCount = $totalPassengers - $dropOnlyCount;

        // Counts per van
        $vanCounts = TransportRosterPassenger::where('is_active', true)
            ->selectRaw('van_number, COUNT(*) as count, SUM(amount) as sum_amount')
            ->groupBy('van_number')
            ->get()
            ->keyBy('van_number');

        // Query passengers
        $query = TransportRosterPassenger::with(['student', 'vehicle', 'route'])
            ->where('is_active', true)
            ->when($selectedVan !== 'all', fn($q) => $q->where('van_number', $selectedVan))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('student_name', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%")
                        ->orWhere('van_number', 'like', "%{$search}%");
                });
            })
            ->when($tripFilter === 'drop_only', fn($q) => $q->whereIn(DB::raw('LOWER(morning_time)'), ['eve', 'evening', '-', '']))
            ->when($tripFilter === 'both', fn($q) => $q->whereNotIn(DB::raw('LOWER(morning_time)'), ['eve', 'evening', '-', '']));

        $passengers = $query->orderByRaw("CASE van_number 
                WHEN 'VAN I' THEN 1 
                WHEN 'VAN II' THEN 2 
                WHEN 'VAN III' THEN 3 
                WHEN 'VAN IV' THEN 4 
                WHEN 'VAN V' THEN 5 
                ELSE 6 END")
            ->orderBy('serial_no')
            ->get();

        $routes   = TransportRoute::with(['vehicle', 'stops'])->where('is_active', true)->get();
        $vehicles = Vehicle::where('is_active', true)->get();

        return view('transport.roster', compact(
            'passengers',
            'vansList',
            'selectedVan',
            'search',
            'tripFilter',
            'totalPassengers',
            'totalBilling',
            'dropOnlyCount',
            'bothTripsCount',
            'vanCounts',
            'routes',
            'vehicles'
        ));
    }

    public function storeRosterPassenger(Request $request)
    {
        $validated = $request->validate([
            'van_number'   => 'required|string|in:VAN I,VAN II,VAN III,VAN IV,VAN V',
            'student_name' => 'required|string|max:150',
            'class_name'   => 'required|string|max:50',
            'area'         => 'required|string|max:150',
            'amount'       => 'required|numeric|min:0',
            'morning_time' => 'nullable|string|max:50',
            'evening_time' => 'nullable|string|max:50',
        ]);

        $routeMap = [
            'VAN I'   => ['route_id' => 4, 'vehicle_id' => 5, 'label' => 'Van I (TN33AX1001)'],
            'VAN II'  => ['route_id' => 5, 'vehicle_id' => 6, 'label' => 'Van II (TN33AX1002)'],
            'VAN III' => ['route_id' => 6, 'vehicle_id' => 7, 'label' => 'Van III (TN33AX1003)'],
            'VAN IV'  => ['route_id' => 7, 'vehicle_id' => 8, 'label' => 'Van IV (TN33AX1004)'],
            'VAN V'   => ['route_id' => 8, 'vehicle_id' => 9, 'label' => 'Van V (TN33AX1005 - Junior & KG)'],
        ];

        $nextSerial = (int) TransportRosterPassenger::where('van_number', $validated['van_number'])->max('serial_no') + 1;
        $map = $routeMap[$validated['van_number']] ?? [];

        TransportRosterPassenger::create([
            'van_number'   => $validated['van_number'],
            'van_label'    => $map['label'] ?? $validated['van_number'],
            'route_id'     => $map['route_id'] ?? null,
            'vehicle_id'   => $map['vehicle_id'] ?? null,
            'serial_no'    => $nextSerial,
            'student_name' => strtoupper(trim($validated['student_name'])),
            'class_name'   => strtoupper(trim($validated['class_name'])),
            'area'         => strtoupper(trim($validated['area'])),
            'amount'       => $validated['amount'],
            'morning_time' => $validated['morning_time'] ?: '—',
            'evening_time' => $validated['evening_time'] ?: '—',
            'is_active'    => true,
        ]);

        return back()->with('success', "Passenger {$validated['student_name']} added to {$validated['van_number']} roster.");
    }

    public function updateRosterPassenger(Request $request, int $id)
    {
        $passenger = TransportRosterPassenger::findOrFail($id);
        $validated = $request->validate([
            'van_number'   => 'required|string|in:VAN I,VAN II,VAN III,VAN IV,VAN V',
            'student_name' => 'required|string|max:150',
            'class_name'   => 'required|string|max:50',
            'area'         => 'required|string|max:150',
            'amount'       => 'required|numeric|min:0',
            'morning_time' => 'nullable|string|max:50',
            'evening_time' => 'nullable|string|max:50',
        ]);

        $routeMap = [
            'VAN I'   => ['route_id' => 4, 'vehicle_id' => 5, 'label' => 'Van I (TN33AX1001)'],
            'VAN II'  => ['route_id' => 5, 'vehicle_id' => 6, 'label' => 'Van II (TN33AX1002)'],
            'VAN III' => ['route_id' => 6, 'vehicle_id' => 7, 'label' => 'Van III (TN33AX1003)'],
            'VAN IV'  => ['route_id' => 7, 'vehicle_id' => 8, 'label' => 'Van IV (TN33AX1004)'],
            'VAN V'   => ['route_id' => 8, 'vehicle_id' => 9, 'label' => 'Van V (TN33AX1005 - Junior & KG)'],
        ];

        $map = $routeMap[$validated['van_number']] ?? [];

        $passenger->update([
            'van_number'   => $validated['van_number'],
            'van_label'    => $map['label'] ?? $passenger->van_label,
            'route_id'     => $map['route_id'] ?? $passenger->route_id,
            'vehicle_id'   => $map['vehicle_id'] ?? $passenger->vehicle_id,
            'student_name' => strtoupper(trim($validated['student_name'])),
            'class_name'   => strtoupper(trim($validated['class_name'])),
            'area'         => strtoupper(trim($validated['area'])),
            'amount'       => $validated['amount'],
            'morning_time' => $validated['morning_time'] ?: '—',
            'evening_time' => $validated['evening_time'] ?: '—',
        ]);

        return back()->with('success', "Passenger {$passenger->student_name} updated successfully.");
    }

    public function deleteRosterPassenger(int $id)
    {
        $passenger = TransportRosterPassenger::findOrFail($id);
        $name = $passenger->student_name;
        $passenger->delete();
        return back()->with('success', "Passenger {$name} removed from roster.");
    }

    public function createVehicle()
    {
        $routes = TransportRoute::where('is_active', true)->orderBy('route_name')->get();
        return view('transport.vehicle-create', compact('routes'));
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number'        => 'required|string|max:30|unique:vehicles,vehicle_number',
            'registration_number'   => 'nullable|string|max:30',
            'make'                  => 'nullable|string|max:60',
            'model'                 => 'nullable|string|max:60',
            'seating_capacity'      => 'required|integer|min:1',
            'vehicle_type'          => 'nullable|string',
            'driver_name'           => 'nullable|string|max:100',
            'driver_mobile'         => 'nullable|string|max:20',
            'driver_license'        => 'nullable|string|max:50',
            'driver_license_expiry' => 'nullable|date',
            'driver_address'        => 'nullable|string',
            'attendant_name'        => 'nullable|string|max:100',
            'attendant_mobile'      => 'nullable|string|max:20',
            'attendant_address'     => 'nullable|string',
            'attendant_aadhaar'     => 'nullable|string|max:20',
            'route_id'              => 'nullable|exists:transport_routes,id',
            'fitness_expiry'        => 'nullable|date',
            'insurance_expiry'      => 'nullable|date',
            'permit_expiry'         => 'nullable|date',
            'puc_expiry'            => 'nullable|date',
            'tax_expiry'            => 'nullable|date',
        ]);

        $validated['capacity'] = $validated['seating_capacity'];
        $validated['is_active'] = true;
        $validated['status'] = 'active';

        $vehicle = Vehicle::create($validated);

        // If route assigned, update route's vehicle_id
        if (!empty($validated['route_id'])) {
            TransportRoute::where('id', $validated['route_id'])->update(['vehicle_id' => $vehicle->id]);
        }

        // If attendant entered, sync to transport_attendants
        if (!empty($validated['attendant_name'])) {
            TransportAttendant::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                [
                    'name'      => $validated['attendant_name'],
                    'mobile'    => $validated['attendant_mobile'] ?? null,
                    'aadhaar'   => $validated['attendant_aadhaar'] ?? null,
                    'route_id'  => $validated['route_id'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('transport.vehicle.show', $vehicle->id)->with('success', "Vehicle {$vehicle->vehicle_number} created successfully.");
    }

    public function createRoute()
    {
        $vehicles = Vehicle::where('is_active', true)->orderBy('vehicle_number')->get();
        return view('transport.route-create', compact('vehicles'));
    }

    public function editRoute(int $id)
    {
        $route    = TransportRoute::findOrFail($id);
        $vehicles = Vehicle::where('is_active', true)->orderBy('vehicle_number')->get();
        return view('transport.route-edit', compact('route', 'vehicles'));
    }

    public function updateRoute(Request $request, int $id)
    {
        $route = TransportRoute::findOrFail($id);
        $request->validate(['route_name' => 'required|string|max:100', 'vehicle_id' => 'nullable|exists:vehicles,id']);
        $route->update([
            'route_name'     => $request->route_name,
            'route_code'     => $request->route_code,
            'vehicle_id'     => $request->vehicle_id,
            'from_location'  => $request->start_point,
            'to_location'    => $request->end_point,
            'departure_time' => $request->start_time,
            'arrival_time'   => $request->end_time,
            'distance_km'    => $request->distance_km,
            'fee'            => $request->fare,
        ]);
        return redirect()->route('transport.routes')->with('success', "Route \"{$route->route_name}\" updated.");
    }

    public function storeRoute(Request $request)
    {
        $request->validate([
            'route_name'  => 'required|string|max:100',
            'vehicle_id'  => 'nullable|exists:vehicles,id',
            'distance_km' => 'nullable|numeric|min:0',
            'fare'        => 'nullable|numeric|min:0',
        ]);
        TransportRoute::create([
            'route_name'       => $request->route_name,
            'vehicle_id'       => $request->vehicle_id,
            'from_location'    => $request->start_point,
            'to_location'      => $request->end_point,
            'departure_time'   => $request->start_time,
            'arrival_time'     => $request->end_time,
            'distance_km'      => $request->distance_km,
            'fee'              => $request->fare,
            'is_active'        => true,
        ]);
        return redirect()->route('transport.routes')->with('success', 'Route added.');
    }

    public function createDriver()
    {
        $vehicles = Vehicle::where('is_active', true)->orderBy('vehicle_number')->get();
        return view('transport.driver-create', compact('vehicles'));
    }

    public function storeDriver(Request $request)
    {
        $request->validate([
            'vehicle_id'      => 'required|exists:vehicles,id',
            'driver_name'     => 'required|string|max:100',
            'driver_mobile'   => 'required|string|max:15',
            'license_number'  => 'nullable|string|max:20',
            'license_expiry'  => 'nullable|date',
        ]);
        Vehicle::findOrFail($request->vehicle_id)->update($request->only(['driver_name', 'driver_mobile', 'license_number', 'license_expiry']));
        return redirect()->route('transport.drivers')->with('success', 'Driver assigned.');
    }

    public function stops(Request $request)
    {
        $routes   = TransportRoute::where('is_active', true)->with('vehicle')->get();
        $vehicles = Vehicle::where('is_active', true)->orderBy('vehicle_number')->get();
        $stops    = TransportStop::with(['route.vehicle', 'vehicle'])
            ->when($request->route_id, fn($q, $v) => $q->where('route_id', $v))
            ->orderBy('route_id')
            ->orderBy('stop_order')
            ->paginate(25)->withQueryString();
        return view('transport.stops', compact('routes', 'stops', 'vehicles'));
    }

    public function storeStop(Request $request)
    {
        $request->validate([
            'route_id'    => 'required|exists:transport_routes,id',
            'name'        => 'required|string|max:100',
            'stop_order'  => 'required|integer|min:1',
            'vehicle_id'  => 'nullable|exists:vehicles,id',
            'van_number'  => 'nullable|string|max:50',
            'pickup_time' => 'nullable',
            'drop_time'   => 'nullable',
            'fare'        => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
        ]);

        $vanNumber = $request->van_number;
        if (!$vanNumber && $request->vehicle_id) {
            $vanNumber = Vehicle::find($request->vehicle_id)?->vehicle_number;
        }

        TransportStop::create(array_merge(
            $request->only(['route_id', 'name', 'stop_order', 'vehicle_id', 'pickup_time', 'drop_time', 'fare', 'landmark', 'distance_km']),
            ['van_number' => $vanNumber]
        ));
        return back()->with('success', 'Stop added successfully.');
    }

    public function editStop(int $id)
    {
        $stop     = TransportStop::findOrFail($id);
        $routes   = TransportRoute::where('is_active', true)->with('vehicle')->get();
        $vehicles = Vehicle::where('is_active', true)->orderBy('vehicle_number')->get();
        return view('transport.stop-edit', compact('stop', 'routes', 'vehicles'));
    }

    public function updateStop(Request $request, int $id)
    {
        $request->validate([
            'route_id'    => 'required|exists:transport_routes,id',
            'name'        => 'required|string|max:100',
            'stop_order'  => 'required|integer|min:1',
            'vehicle_id'  => 'nullable|exists:vehicles,id',
            'van_number'  => 'nullable|string|max:50',
            'pickup_time' => 'nullable',
            'drop_time'   => 'nullable',
            'fare'        => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
        ]);

        $vanNumber = $request->van_number;
        if (!$vanNumber && $request->vehicle_id) {
            $vanNumber = Vehicle::find($request->vehicle_id)?->vehicle_number;
        }

        TransportStop::findOrFail($id)->update(array_merge(
            $request->only(['route_id', 'name', 'stop_order', 'vehicle_id', 'pickup_time', 'drop_time', 'fare', 'landmark', 'distance_km']),
            ['van_number' => $vanNumber]
        ));
        return redirect()->route('transport.stops')->with('success', 'Stop updated successfully.');
    }

    public function deleteStop(int $id)
    {
        TransportStop::findOrFail($id)->delete();
        return back()->with('success', 'Stop removed.');
    }

    public function attendants(Request $request)
    {
        $attendants = TransportAttendant::with(['vehicle', 'route'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')->paginate(20);
        $vehicles = Vehicle::where('is_active', true)->get();
        $routes   = TransportRoute::where('is_active', true)->get();
        return view('transport.attendants', compact('attendants', 'vehicles', 'routes'));
    }

    public function storeAttendant(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'mobile'     => 'nullable|string|max:15',
            'aadhaar'    => 'nullable|string|max:12',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'route_id'   => 'nullable|exists:transport_routes,id',
        ]);
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('attendants', 'public');
        }
        TransportAttendant::create($data);
        return back()->with('success', 'Attendant added successfully.');
    }

    public function updateAttendant(Request $request, int $id)
    {
        $attendant = TransportAttendant::findOrFail($id);
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'mobile'     => 'nullable|string|max:15',
            'aadhaar'    => 'nullable|string|max:12',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'route_id'   => 'nullable|exists:transport_routes,id',
            'is_active'  => 'boolean',
        ]);
        $attendant->update($data);
        return back()->with('success', 'Attendant updated.');
    }

    public function deleteAttendant(int $id)
    {
        TransportAttendant::findOrFail($id)->delete();
        return back()->with('success', 'Attendant removed.');
    }

    public function vehicleUtilisation(Request $request)
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $routesByVehicle = TransportRoute::where('is_active', true)
            ->withCount(['allotments as assigned' => fn($q) => $q->where('is_active', true)])
            ->get()->keyBy('vehicle_id');
        $data = $vehicles->map(function ($vehicle) use ($routesByVehicle) {
            $route    = $routesByVehicle->get($vehicle->id);
            $assigned = $route?->assigned ?? 0;
            $capacity = $vehicle->capacity ?? 0;
            return [
                'vehicle'     => $vehicle,
                'route'       => $route,
                'capacity'    => $capacity,
                'assigned'    => $assigned,
                'available'   => max(0, $capacity - $assigned),
                'utilisation' => $capacity > 0 ? round(($assigned / $capacity) * 100, 1) : 0,
            ];
        });
        return view('transport.vehicle-utilisation', compact('data'));
    }

    /* ------------------------------------------------------------------ */
    /*  Fuel Expense Monthly Summary                                        */
    public function emergencyContacts(Request $request)
    {
        $routes   = TransportRoute::where('is_active', true)->orderBy('route_name')->get();
        $routeId  = $request->route_id;
        $contacts = [];

        if ($routeId) {
            $contacts = DB::table('transport_emergency_contacts')
                ->where('route_id', $routeId)
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->get();
        }

        return view('transport.emergency-contacts', compact('routes', 'contacts', 'routeId'));
    }

    public function storeEmergencyContact(Request $request)
    {
        $request->validate([
            'route_id'     => 'required|exists:transport_routes,id',
            'name'         => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'relationship' => 'nullable|string|max:50',
        ]);
        DB::table('transport_emergency_contacts')->insert([
            'route_id'     => $request->route_id,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'relationship' => $request->relationship,
            'designation'  => $request->designation,
            'is_primary'   => $request->boolean('is_primary'),
            'notes'        => $request->notes,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        return back()->with('success', 'Emergency contact added.');
    }

    public function deleteEmergencyContact(int $id)
    {
        DB::table('transport_emergency_contacts')->where('id', $id)->delete();
        return back()->with('success', 'Contact removed.');
    }

    /* ------------------------------------------------------------------ */

    public function fuelSummary(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $month    = $request->month ?? now()->format('Y-m');
        [$yr, $mo] = explode('-', $month);

        $fuelByVehicle = VehicleFuelLog::select(
                'vehicle_id',
                DB::raw('SUM(quantity_litres) as total_litres'),
                DB::raw('SUM(total_cost) as total_amount'),
                DB::raw('COUNT(*) as fill_count'),
                DB::raw('MAX(odometer_reading) - MIN(odometer_reading) as km_covered')
            )
            ->whereYear('log_date', $yr)->whereMonth('log_date', $mo)
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->get();

        $grandTotals = [
            'litres' => $fuelByVehicle->sum('total_litres'),
            'amount' => $fuelByVehicle->sum('total_amount'),
        ];

        return view('transport.fuel-summary', compact('fuelByVehicle', 'grandTotals', 'month', 'vehicles'));
    }

    public function fuelSummaryPdf(Request $request)
    {
        $month  = $request->month ?? now()->format('Y-m');
        [$yr, $mo] = explode('-', $month);
        $fuelByVehicle = VehicleFuelLog::select(
                'vehicle_id',
                DB::raw('SUM(quantity_litres) as total_litres'),
                DB::raw('SUM(total_cost) as total_amount'),
                DB::raw('COUNT(*) as fill_count'),
                DB::raw('MAX(odometer_reading) - MIN(odometer_reading) as km_covered')
            )
            ->whereYear('log_date', $yr)->whereMonth('log_date', $mo)
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->get();
        $grandTotals = ['litres' => $fuelByVehicle->sum('total_litres'), 'amount' => $fuelByVehicle->sum('total_amount')];
        $school = SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.transport-fuel-summary', compact('fuelByVehicle', 'grandTotals', 'month', 'school'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('fuel-summary-' . $month . '.pdf');
    }

    public function fuelSummaryExcel(Request $request)
    {
        $month  = $request->month ?? now()->format('Y-m');
        [$yr, $mo] = explode('-', $month);
        $fuelByVehicle = VehicleFuelLog::select(
                'vehicle_id',
                DB::raw('SUM(quantity_litres) as total_litres'),
                DB::raw('SUM(total_cost) as total_amount'),
                DB::raw('COUNT(*) as fill_count'),
                DB::raw('MAX(odometer_reading) - MIN(odometer_reading) as km_covered')
            )
            ->whereYear('log_date', $yr)->whereMonth('log_date', $mo)
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->get();

        $rows = $fuelByVehicle->map(fn($row) => [
            'Vehicle'      => $row->vehicle?->vehicle_number ?? ('Vehicle #' . $row->vehicle_id),
            'Fill Count'   => $row->fill_count,
            'Total Litres' => number_format($row->total_litres, 2),
            'Total Amount' => '₹' . number_format($row->total_amount, 2),
            'KM Covered'   => $row->km_covered ? number_format($row->km_covered, 0) : '—',
            'Km/Litre'     => ($row->total_litres > 0 && $row->km_covered)
                ? round($row->km_covered / $row->total_litres, 2) : '—',
        ])->toArray();

        return Excel::download(new ArrayExport($rows), 'fuel-summary-' . $month . '.xlsx');
    }

    /* ------------------------------------------------------------------ */
    /*  Roster PDF Export                                                   */
    /* ------------------------------------------------------------------ */

    public function rosterPdf(Request $request)
    {
        $selectedVan = $request->get('van', 'all');
        $school = SchoolSetting::first();

        $passengersQuery = TransportRosterPassenger::with(['route', 'vehicle'])
            ->where('is_active', true)
            ->when($selectedVan !== 'all', fn($q) => $q->where('van_number', $selectedVan))
            ->orderByRaw("CASE van_number 
                WHEN 'VAN I' THEN 1 
                WHEN 'VAN II' THEN 2 
                WHEN 'VAN III' THEN 3 
                WHEN 'VAN IV' THEN 4 
                WHEN 'VAN V' THEN 5 
                ELSE 6 END")
            ->orderBy('serial_no');

        $passengers = $passengersQuery->get()->groupBy('van_number');

        $vansMeta = [
            'VAN I'   => ['title' => 'VAN I — RN Pudur / Bhavani / Thindal', 'vehicle' => 'TN33AX1001 (Cruiser 24)', 'route_no' => 'VAN-01'],
            'VAN II'  => ['title' => 'VAN II — Moolapalayam / Periyar Nagar / Surampatti', 'vehicle' => 'TN33AX1002 (Cruiser 24)', 'route_no' => 'VAN-02'],
            'VAN III' => ['title' => 'VAN III — Soolai / EPB Nagar / Mokkiayanpalayam', 'vehicle' => 'TN33AX1003 (Cruiser 24)', 'route_no' => 'VAN-03'],
            'VAN IV'  => ['title' => 'VAN IV — Karungalpalayam / Thirunagar Colony', 'vehicle' => 'TN33AX1004 (Cruiser 24)', 'route_no' => 'VAN-04'],
            'VAN V'   => ['title' => 'VAN V — Junior & KG Special Route', 'vehicle' => 'TN33AX1005 (Cruiser 24)', 'route_no' => 'VAN-05'],
        ];

        $totalStudents = TransportRosterPassenger::where('is_active', true)->when($selectedVan !== 'all', fn($q) => $q->where('van_number', $selectedVan))->count();
        $totalFare = TransportRosterPassenger::where('is_active', true)->when($selectedVan !== 'all', fn($q) => $q->where('van_number', $selectedVan))->sum('amount');

        $pdf = Pdf::loadView('pdf.transport-roster', compact('passengers', 'vansMeta', 'school', 'selectedVan', 'totalStudents', 'totalFare'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream('transport-roster-' . ($selectedVan !== 'all' ? str_replace(' ', '-', strtolower($selectedVan)) . '-' : '') . now()->format('Y-m-d') . '.pdf');
    }

    public function rosterExcel(Request $request)
    {
        $selectedVan = $request->get('van', 'all');
        $passengers = TransportRosterPassenger::with(['route', 'vehicle'])
            ->where('is_active', true)
            ->when($selectedVan !== 'all', fn($q) => $q->where('van_number', $selectedVan))
            ->orderByRaw("CASE van_number 
                WHEN 'VAN I' THEN 1 
                WHEN 'VAN II' THEN 2 
                WHEN 'VAN III' THEN 3 
                WHEN 'VAN IV' THEN 4 
                WHEN 'VAN V' THEN 5 
                ELSE 6 END")
            ->orderBy('serial_no')
            ->get();

        $rows = [];
        $rows[] = ['S.No', 'Van', 'Route Code', 'Bus Reg No', 'Student Name', 'Class', 'Area / Boarding Point', 'Morning Pickup', 'Evening Drop', 'Monthly Fare (₹)', 'Trip Type'];

        foreach ($passengers as $p) {
            $isDropOnly = in_array(strtolower(trim((string)$p->morning_time)), ['eve', 'evening', '-', '']);
            $rows[] = [
                $p->serial_no,
                $p->van_number,
                $p->route?->route_number ?? '—',
                $p->vehicle?->vehicle_number ?? ($p->van_label ?? '—'),
                $p->student_name,
                $p->class_name,
                $p->area,
                $isDropOnly ? 'Evening Drop Only' : ($p->morning_time ?: '—'),
                $p->evening_time ?: '—',
                number_format($p->amount, 2),
                $isDropOnly ? 'Evening Drop Only' : 'Both Morning & Evening',
            ];
        }

        $filename = 'transport-roster-' . ($selectedVan !== 'all' ? str_replace(' ', '-', strtolower($selectedVan)) . '-' : '') . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ArrayExport($rows), $filename);
    }

    /* ------------------------------------------------------------------ */
    /*  Maintenance Cost per Vehicle Report                                 */
    /* ------------------------------------------------------------------ */

    public function maintenanceCostReport(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $year     = $request->year ?? now()->year;

        $perVehicle = $vehicles->map(function ($v) use ($year) {
            $logs = VehicleMaintenance::where('vehicle_id', $v->id)
                ->whereYear('service_date', $year)
                ->get();
            $byMonth = $logs->groupBy(fn($l) => \Carbon\Carbon::parse($l->service_date)->format('m'))
                ->map(fn($g) => $g->sum('cost'));
            return [
                'vehicle'       => $v,
                'logs'          => $logs,
                'total'         => $logs->sum('cost'),
                'breakdown_count' => $logs->where('maintenance_type','breakdown')->count(),
                'scheduled_count' => $logs->where('maintenance_type','scheduled')->count(),
                'by_month'      => $byMonth,
                'last_service'  => $logs->sortByDesc('service_date')->first()?->service_date,
                'next_service'  => $logs->sortByDesc('service_date')->first()?->next_service_date,
            ];
        });

        $grandTotal  = $perVehicle->sum('total');
        $months      = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => \Carbon\Carbon::create(null, $m)->format('M')]);

        return view('transport.maintenance-cost', compact('perVehicle', 'grandTotal', 'vehicles', 'year', 'months'));
    }

    public function maintenanceCostPdf(Request $request)
    {
        $year     = $request->year ?? now()->year;
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $school   = SchoolSetting::first();

        $perVehicle = $vehicles->map(function ($v) use ($year) {
            $logs = VehicleMaintenance::where('vehicle_id', $v->id)->whereYear('service_date', $year)->get();
            return ['vehicle' => $v, 'logs' => $logs, 'total' => $logs->sum('cost')];
        });
        $grandTotal = $perVehicle->sum('total');

        $pdf = Pdf::loadView('pdf.transport-maintenance-cost', compact('perVehicle', 'grandTotal', 'school', 'year'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('maintenance-cost-' . $year . '.pdf');
    }

    public function maintenanceCostExcel(Request $request)
    {
        $year     = $request->year ?? now()->year;
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $rows     = [];
        $rows[]   = ['Vehicle', 'Reg Number', 'Date', 'Type', 'Work Done', 'Vendor', 'Cost (₹)', 'Next Service'];
        foreach ($vehicles as $v) {
            $logs = VehicleMaintenance::where('vehicle_id', $v->id)->whereYear('service_date', $year)->orderBy('service_date')->get();
            if ($logs->isEmpty()) continue;
            foreach ($logs as $l) {
                $rows[] = [
                    $v->vehicle_number,
                    $v->vehicle_number,
                    \Carbon\Carbon::parse($l->service_date)->format('d M Y'),
                    ucfirst($l->maintenance_type),
                    $l->work_done,
                    $l->vendor ?? '—',
                    $l->cost,
                    $l->next_service_date ? \Carbon\Carbon::parse($l->next_service_date)->format('d M Y') : '—',
                ];
            }
            $rows[] = ['', '', '', '', 'Vehicle Total:', '', $logs->sum('cost'), ''];
            $rows[] = ['', '', '', '', '', '', '', ''];
        }
        return Excel::download(new ArrayExport($rows), 'maintenance-cost-' . $year . '.xlsx');
    }

    // ── Bus Attendance Register ───────────────────────────

    public function busAttendance(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $routes   = TransportRoute::where('is_active', true)->orderBy('route_name')->get();

        $date      = $request->date ?? today()->toDateString();
        $vehicleId = $request->vehicle_id;
        $tripType  = $request->trip_type ?? 'morning';

        $students = collect();
        $existing = collect();

        if ($vehicleId) {
            // Students allotted to this vehicle
            $students = TransportAllotment::with(['student', 'stop'])
                ->where('vehicle_id', $vehicleId)
                ->where('is_active', true)
                ->get()
                ->map(fn($a) => [
                    'student'      => $a->student,
                    'boarding_stop'=> $a->stop?->name ?? $a->boarding_stop ?? '',
                ])
                ->filter(fn($a) => $a['student'] !== null);

            $existing = BusAttendance::where('vehicle_id', $vehicleId)
                ->where('date', $date)
                ->where('trip_type', $tripType)
                ->pluck('status', 'student_id');
        }

        return view('transport.bus-attendance', compact(
            'vehicles', 'routes', 'date', 'vehicleId', 'tripType', 'students', 'existing'
        ));
    }

    public function saveBusAttendance(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'date'       => 'required|date',
            'trip_type'  => 'required|in:morning,afternoon,both',
            'attendance'  => 'nullable|array',
        ]);

        $attendance = $request->input('attendance', []);

        foreach ($attendance as $studentId => $status) {
            BusAttendance::updateOrCreate(
                [
                    'vehicle_id' => $request->vehicle_id,
                    'date'       => $request->date,
                    'trip_type'  => $request->trip_type,
                    'student_id' => $studentId,
                ],
                [
                    'route_id'  => $request->route_id,
                    'status'    => $status,
                    'marked_by' => Auth::id(),
                ]
            );
        }

        return back()->with('success', count($attendance) . ' attendance records saved.');
    }

    public function busAttendanceReport(Request $request)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();

        $vehicleId = $request->vehicle_id;
        $from      = $request->from ?? today()->startOfMonth()->toDateString();
        $to        = $request->to   ?? today()->toDateString();

        $records = collect();
        if ($vehicleId) {
            $records = BusAttendance::with(['student', 'vehicle'])
                ->where('vehicle_id', $vehicleId)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')->orderBy('student_id')
                ->get()
                ->groupBy('student_id')
                ->map(fn($group) => [
                    'student' => $group->first()->student,
                    'total'   => $group->count(),
                    'present' => $group->where('status', 'present')->count(),
                    'absent'  => $group->where('status', 'absent')->count(),
                ]);
        }

        return view('transport.bus-attendance-report', compact('vehicles', 'vehicleId', 'from', 'to', 'records'));
    }

    public function busAttendanceReportExcel(Request $request)
    {
        $vehicleId = $request->vehicle_id;
        $from      = $request->from ?? today()->startOfMonth()->toDateString();
        $to        = $request->to   ?? today()->toDateString();
        $vehicle   = Vehicle::find($vehicleId);

        $records = BusAttendance::with('student')
            ->when($vehicleId, fn($q) => $q->where('vehicle_id', $vehicleId))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')->orderBy('student_id')
            ->get()
            ->groupBy('student_id')
            ->map(fn($group) => [
                'Student'       => $group->first()->student?->full_name ?? '—',
                'Admission No'  => $group->first()->student?->admission_number ?? '—',
                'Total Days'    => $group->count(),
                'Present'       => $group->where('status', 'present')->count(),
                'Absent'        => $group->where('status', 'absent')->count(),
                'Attendance %'  => $group->count() > 0 ? round($group->where('status','present')->count() / $group->count() * 100) . '%' : '—',
            ])->values()->toArray();

        return Excel::download(
            new ArrayExport($records),
            'bus-attendance-' . ($vehicle?->vehicle_number ?? 'all') . '-' . $from . '-to-' . $to . '.xlsx'
        );
    }
}


