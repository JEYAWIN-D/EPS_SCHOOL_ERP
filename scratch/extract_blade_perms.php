<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$canDirectives = [];

foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/@can\([\'"]([^\'"]+)[\'"]\)/', $content, $m)) {
            foreach ($m[1] as $p) {
                $canDirectives[trim($p)][] = $file->getPathname();
            }
        }
    }
}

ksort($canDirectives);
echo "Total distinct permissions in @can directives: " . count($canDirectives) . "\n\n";

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class);
$k->bootstrap();

$existingPerms = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
$missing = [];

foreach ($canDirectives as $perm => $paths) {
    $exists = in_array($perm, $existingPerms);
    if (!$exists) {
        $missing[] = $perm;
        echo "[MISSING IN DB] '$perm' (Used in " . count($paths) . " views, e.g. " . basename($paths[0]) . ")\n";
    }
}

if (empty($missing)) {
    echo "All @can permissions exist in DB!\n";
} else {
    echo "\nTotal missing @can permissions: " . count($missing) . "\n";
}
