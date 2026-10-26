<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\TransportAllotment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=================================================================\n";
echo "CLEARING ALL STUDENTS FROM ALL TRANSPORT VANS\n";
echo "=================================================================\n\n";

DB::transaction(function() {
    // 1. Delete all student transport allotments
    $deletedAllotments = TransportAllotment::query()->delete();
    echo "Deleted {$deletedAllotments} transport allotment records.\n";

    // 2. Clear offline/shadow roster passengers if table exists
    if (Schema::hasTable('transport_roster_passengers')) {
        $deletedRoster = DB::table('transport_roster_passengers')->delete();
        echo "Deleted {$deletedRoster} shadow roster passenger records.\n";
    }

    // 3. Reset transport columns in students table
    $studentsUpdated = Student::where(function($q) {
        $q->whereNotNull('transport_route_id')
          ->orWhereNotNull('transport_stop_id')
          ->orWhereNotNull('transport_distance_km')
          ->orWhere('transport_fee', '>', 0);
    })->update([
        'transport_route_id'    => null,
        'transport_stop_id'     => null,
        'transport_distance_km' => null,
        'transport_fee'         => 0,
    ]);
    echo "Reset transport columns for {$studentsUpdated} students.\n";

    // 4. Optionally clear old assignment history
    if (Schema::hasTable('transport_assignment_histories')) {
        $deletedHistory = DB::table('transport_assignment_histories')->delete();
        echo "Cleared {$deletedHistory} transport assignment history logs.\n";
    }
});

// Clear cache
Cache::flush();
echo "Flushed application cache.\n\n";

// Verify counts
$activeAllotments = TransportAllotment::count();
$studentsWithTransport = Student::whereNotNull('transport_route_id')->count();
$routesCount = TransportRoute::count();
$stopsCount = TransportStop::where('is_active', true)->count();

echo "=================================================================\n";
echo "VERIFICATION AFTER RESET:\n";
echo "  Active Transport Allotments: {$activeAllotments} (Expected: 0)\n";
echo "  Students with Transport Route: {$studentsWithTransport} (Expected: 0)\n";
echo "  Active Corridors (Vans): {$routesCount} (Preserved)\n";
echo "  Active Bus Stops: {$stopsCount} (Preserved 57 stops)\n";
echo "=================================================================\n";

if ($activeAllotments === 0 && $studentsWithTransport === 0 && $stopsCount === 57) {
    echo "🎉 SUCCESS: All vans are completely blank and ready for manual admissions!\n";
} else {
    echo "⚠️ Warning: Counts did not match expectations.\n";
}
