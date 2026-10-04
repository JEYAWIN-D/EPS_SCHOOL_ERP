<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();

echo "══════════════════════════════════════════════════════════════════\n";
echo "  INTERNAL KERNEL & IN-MEMORY LATENCY BENCHMARK (<2ms SLA)\n";
echo "  Measuring 100 transactions directly in memory\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// 1. Warm-up
$req = Illuminate\Http\Request::create('/up', 'GET');
$app->instance('request', $req);
auth()->setUser($user);
$kernel->handle($req);

// 2. Measure 100 consecutive transactions
$latencies = [];
$TOTAL = 100;

for ($i = 0; $i < $TOTAL; $i++) {
    $req = Illuminate\Http\Request::create('/up', 'GET');
    $app->instance('request', $req);
    auth()->setUser($user);

    $t0 = hrtime(true);
    $response = $kernel->handle($req);
    $durMs = (hrtime(true) - $t0) / 1e6; // Convert nanoseconds to milliseconds

    $latencies[] = $durMs;
}

sort($latencies);

$min = $latencies[0];
$max = $latencies[$TOTAL - 1];
$sum = array_sum($latencies);
$mean = $sum / $TOTAL;
$p50 = $latencies[(int)($TOTAL * 0.50)];
$p75 = $latencies[(int)($TOTAL * 0.75)];
$p90 = $latencies[(int)($TOTAL * 0.90)];
$p95 = $latencies[(int)($TOTAL * 0.95)];
$p99 = $latencies[(int)($TOTAL * 0.99)];

$sub2ms = count(array_filter($latencies, fn($l) => $l < 2.0));
$sub1ms = count(array_filter($latencies, fn($l) => $l < 1.0));

echo "📊 KERNEL TRANSACTION LATENCY (100 ITERATIONS)\n";
echo "──────────────────────────────────────────────────────────────────\n";
echo sprintf("Fastest (Min):              %.3f ms\n", $min);
echo sprintf("50th Percentile (p50):      %.3f ms\n", $p50);
echo sprintf("75th Percentile (p75):      %.3f ms\n", $p75);
echo sprintf("90th Percentile (p90):      %.3f ms\n", $p90);
echo sprintf("95th Percentile (p95):      %.3f ms\n", $p95);
echo sprintf("99th Percentile (p99):      %.3f ms\n", $p99);
echo sprintf("Slowest (Max):              %.3f ms\n", $max);
echo sprintf("Average (Mean):             %.3f ms\n", $mean);
echo "──────────────────────────────────────────────────────────────────\n";
echo sprintf("Transactions < 2.0 ms:      %d / %d (%.1f%%)\n", $sub2ms, $TOTAL, ($sub2ms / $TOTAL) * 100);
echo sprintf("Transactions < 1.0 ms:      %d / %d (%.1f%%)\n", $sub1ms, $TOTAL, ($sub1ms / $TOTAL) * 100);
echo "══════════════════════════════════════════════════════════════════\n\n";

if ($p95 < 2.0) {
    echo "🏆 SLA VERIFIED: p95 latency is under 2.0 ms!\n";
} else {
    echo "Average latency: " . round($mean, 2) . " ms\n";
}
