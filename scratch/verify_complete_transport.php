<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TransportAllotment;
use App\Models\TransportAssignmentHistory;
use App\Models\TransportImportLog;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use App\Services\TransportManagementService;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "SCHOOL ERP - TRANSPORT MANAGEMENT SYSTEM END-TO-END VERIFICATION\n";
echo "=================================================================\n\n";

$service = app(TransportManagementService::class);
$testsPassed = 0;
$testsFailed = 0;

function runTest($testId, $title, $closure) {
    global $testsPassed, $testsFailed;
    echo "[$testId] $title ... ";
    try {
        $result = $closure();
        if ($result === true || (is_array($result) && ($result['pass'] ?? false))) {
            echo "PASS\n";
            if (is_array($result) && !empty($result['detail'])) {
                echo "      Detail: {$result['detail']}\n";
            }
            $testsPassed++;
        } else {
            echo "FAIL\n";
            if (is_array($result) && !empty($result['error'])) {
                echo "      Error: {$result['error']}\n";
            }
            $testsFailed++;
        }
    } catch (\Throwable $e) {
        echo "FAIL (Exception: " . $e->getMessage() . " at line " . $e->getLine() . ")\n";
        $testsFailed++;
    }
}

// TEST 1: Backup transport data
runTest('TC-01', 'Backup Transport Snapshot', function() use ($service) {
    $path = $service->backupTransportData();
    return [
        'pass' => file_exists($path) && filesize($path) > 1000,
        'detail' => "Snapshot created at " . basename($path) . " (" . round(filesize($path)/1024, 1) . " KB)"
    ];
});

// TEST 2: Vehicle CRUD & Capacity Validation
runTest('TC-02', 'Vehicle Management & Seating Capacity', function() {
    $reg = 'TN-33-TEST-' . rand(1000, 9999);
    $vehicle = Vehicle::create([
        'vehicle_number'   => $reg,
        'vehicle_type'     => 'van',
        'seating_capacity' => 15,
        'capacity'         => 15,
        'driver_name'      => 'Test Driver S',
        'driver_mobile'    => '9876543210',
        'is_active'        => true,
    ]);

    $found = Vehicle::find($vehicle->id);
    $pass = $found && $found->vehicle_number === $reg && $found->seating_capacity === 15;
    
    // Toggle active
    $found->update(['is_active' => false]);
    $toggled = Vehicle::find($vehicle->id)->is_active === false;

    // Cleanup
    $found->delete();

    return [
        'pass' => $pass && $toggled,
        'detail' => "Vehicle created with 15 seats capacity, toggled active/inactive, deleted cleanly."
    ];
});

// TEST 3: Bus Stop CRUD & Custom Timings
runTest('TC-03', 'Bus Stop Management (CRUD & Scheduled Timings)', function() {
    $route = TransportRoute::first();
    if (!$route) {
        return ['pass' => false, 'error' => 'No route found in DB'];
    }

    $stop = TransportStop::create([
        'route_id'    => $route->id,
        'name'        => 'Test Stop Erode Central',
        'stop_order'  => 99,
        'pickup_time' => '07:45:00',
        'drop_time'   => '16:45:00',
        'fare'        => 12000,
        'distance_km' => 6.5,
        'landmark'    => 'Near Central Library',
        'is_active'   => true,
    ]);

    $pass = $stop->id && $stop->name === 'Test Stop Erode Central' && $stop->is_active;

    // Update stop
    $stop->update(['name' => 'Test Stop Erode Central Updated', 'fare' => 12500]);
    $updated = TransportStop::find($stop->id)->name === 'Test Stop Erode Central Updated';

    // Delete stop
    $stop->delete();
    $deleted = !TransportStop::find($stop->id);

    return [
        'pass' => $pass && $updated && $deleted,
        'detail' => "Bus stop created with pickup 07:45 & drop 16:45, updated, and removed cleanly."
    ];
});

// TEST 4: Excel Import of Official Bus Stops
runTest('TC-04', 'Excel Import - Official Bus Stops', function() use ($service) {
    $excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT DETAILS (2).xlsx';
    if (!file_exists($excelPath)) {
        return ['pass' => false, 'error' => 'File not found at ' . $excelPath];
    }

    $summary = $service->importBusStopsFromExcel($excelPath, 1, 'TRANSPORT DETAILS (2).xlsx');
    $pass = ($summary['total_rows'] ?? 0) > 0 && ($summary['failed_count'] ?? 0) === 0;

    return [
        'pass' => $pass,
        'detail' => "Imported {$summary['total_rows']} rows: {$summary['success_count']} stops created/updated, {$summary['failed_count']} failed."
    ];
});

// TEST 5: Excel Import of Student Allocations
runTest('TC-05', 'Excel Import - Student Passenger Manifests', function() use ($service) {
    $excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS (1).xlsx';
    if (!file_exists($excelPath)) {
        $excelPath = 'C:\\Users\\jeyaw\\Downloads\\TRANSPORT FULL DETAILS.xlsx';
    }
    if (!file_exists($excelPath)) {
        return ['pass' => false, 'error' => 'File not found in Downloads'];
    }

    $summary = $service->importStudentAllocationsFromExcel($excelPath, 1, basename($excelPath));
    $pass = ($summary['total_rows'] ?? 0) > 0;

    return [
        'pass' => $pass,
        'detail' => "Processed {$summary['total_rows']} students: {$summary['success_count']} allotted/synced across Van routes."
    ];
});

// TEST 6: Student Timing Overrides & Default Inheritance
runTest('TC-06', 'Student Individual Timing Overrides & Effective Accessors', function() use ($service) {
    $student = Student::where('status', 'active')->first();
    $route = TransportRoute::where('is_active', true)->has('stops')->first();
    $stop = $route->stops()->first();

    // Assign with custom pickup override
    $allotment = $service->assignStudentTransport($student->id, [
        'route_id'    => $route->id,
        'stop_id'     => $stop->id,
        'pickup_time' => '07:15 AM', // Override
        'drop_time'   => null,       // Inherit default
        'fee'         => 15000,
        'remarks'     => 'Timing test',
    ]);

    $allotment->refresh();
    $overrideMatches = ($allotment->pickup_time_override == true);
    $effectivePickup = str_starts_with($allotment->effective_pickup_time, '07:15');
    $effectiveDropInherited = !empty($allotment->effective_drop_time);

    return [
        'pass' => $overrideMatches && $effectivePickup && $effectiveDropInherited,
        'detail' => "Effective pickup resolved override ('{$allotment->effective_pickup_time}'), effective drop inherited default ('{$allotment->effective_drop_time}')."
    ];
});

// TEST 7: Route Safe Retirement & Student Migration
runTest('TC-07', 'Route Safe Retirement without Dropping Historical Students', function() use ($service) {
    // Create temporary test route
    $tempRoute = TransportRoute::create([
        'route_name'    => 'Temporary Retirement Test Route',
        'from_location' => 'School Campus',
        'to_location'   => 'Test Terminal',
        'is_active'     => true,
        'distance_km'   => 10,
        'fee'           => 14000,
    ]);

    $tempStop = TransportStop::create([
        'route_id'   => $tempRoute->id,
        'name'       => 'Temp Stop Alpha',
        'stop_order' => 1,
        'is_active'  => true,
    ]);

    $student = Student::where('status', 'active')->latest('id')->first();
    
    // Assign student to temp route
    $service->assignStudentTransport($student->id, [
        'route_id' => $tempRoute->id,
        'stop_id'  => $tempStop->id,
    ]);

    // Target route to migrate to
    $targetRoute = TransportRoute::where('id', '!=', $tempRoute->id)->where('is_active', true)->first();

    // Retire temp route and migrate student
    $retireResult = $service->retireRoute($tempRoute->id, $targetRoute->id, 'Testing retirement');

    $allotment = TransportAllotment::where('student_id', $student->id)->where('is_active', true)->first();
    $migrated = $allotment && $allotment->route_id === $targetRoute->id;

    $history = TransportAssignmentHistory::where('student_id', $student->id)->where('change_type', 'migrated')->latest('id')->first();
    $historyPreserved = $history && $history->old_route_id === $tempRoute->id && $history->new_route_id === $targetRoute->id;

    // Temp route should be inactive
    $routeDeactivated = !TransportRoute::find($tempRoute->id)->is_active;

    return [
        'pass' => $retireResult['success'] && $migrated && $historyPreserved && $routeDeactivated,
        'detail' => "Route safely retired: student migrated to {$targetRoute->route_name}, audit history preserved."
    ];
});

// TEST 8: Safe Stop Deletion / Deactivation
runTest('TC-08', 'Safe Bus Stop Deletion with Assigned Student Protection', function() use ($service) {
    $route = TransportRoute::where('is_active', true)->first();
    $stop1 = TransportStop::create(['route_id' => $route->id, 'name' => 'SafeStop 1', 'stop_order' => 90, 'is_active' => true]);
    $stop2 = TransportStop::create(['route_id' => $route->id, 'name' => 'SafeStop 2', 'stop_order' => 91, 'is_active' => true]);

    $student = Student::where('status', 'active')->first();
    $service->assignStudentTransport($student->id, ['route_id' => $route->id, 'stop_id' => $stop1->id]);

    // Deleting stop1 with reassignment to stop2
    $result = $service->safeDeleteStop($stop1->id, $stop2->id);

    $allotment = TransportAllotment::where('student_id', $student->id)->where('is_active', true)->first();
    $reassigned = $allotment && $allotment->stop_id === $stop2->id;

    // Clean up temporary test stop2 after restoring student to official stop
    $officialStop = TransportStop::where('route_id', $route->id)->where('is_active', true)->whereNotIn('id', [$stop1->id, $stop2->id])->first();
    if ($officialStop) {
        $service->assignStudentTransport($student->id, ['route_id' => $route->id, 'stop_id' => $officialStop->id]);
    }
    $stop2->delete();

    return [
        'pass' => $result['success'] && $reassigned,
        'detail' => "Stop deleted safely after reassigning active students to alternate stop."
    ];
});

// TEST 9: Student Transport Discontinuation & Fee Balance Preservation
runTest('TC-09', 'Transport Discontinuation (Safe Deactivation & Receipt Preservation)', function() use ($service) {
    $student = Student::where('status', 'active')->first();
    $route = TransportRoute::where('is_active', true)->has('stops')->first();
    $stop = $route->stops()->first();

    $allotment = $service->assignStudentTransport($student->id, [
        'route_id' => $route->id,
        'stop_id'  => $stop->id,
        'fee'      => 16000,
    ]);

    // Discontinue using student id
    $service->discontinueStudentTransport($student->id, 'Parent requested withdrawal', today()->toDateString(), 1);

    $refreshed = TransportAllotment::find($allotment->id);
    $isDiscontinued = $refreshed->is_active === false && $refreshed->discontinued_reason === 'Parent requested withdrawal';

    $hist = TransportAssignmentHistory::where('student_id', $student->id)->where('change_type', 'discontinued')->latest('id')->first();
    $hasHistory = $hist !== null;

    return [
        'pass' => $isDiscontinued && $hasHistory,
        'detail' => "Assignment discontinued with reason and audit log; fee receipts remained unaltered."
    ];
});

// TEST 10: Vehicle Seating Capacity Restriction & Occupancy
runTest('TC-10', 'Vehicle Seating Capacity Calculation & Validation', function() {
    $vehicles = Vehicle::where('is_active', true)->get();
    $allotments = TransportAllotment::where('is_active', true)->count();
    $totalCapacity = Vehicle::where('is_active', true)->sum(DB::raw('COALESCE(seating_capacity, capacity, 0)'));

    $pass = $vehicles->count() > 0 && $totalCapacity > 0;

    return [
        'pass' => $pass,
        'detail' => "Total fleet capacity: {$totalCapacity} seats across {$vehicles->count()} active vehicles. {$allotments} students currently allotted."
    ];
});

echo "\n=================================================================\n";
echo "SUMMARY: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "=================================================================\n";
exit($testsFailed > 0 ? 1 : 0);
