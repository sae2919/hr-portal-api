<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logoUrl = App\Models\CompanySetting::where('key', 'company_logo')->value('value');
echo "Logo URL in DB: " . ($logoUrl ?: '(empty)') . PHP_EOL;

$companyName = App\Models\CompanySetting::where('key', 'company_name')->value('value');
echo "Company Name in DB: " . ($companyName ?: '(empty)') . PHP_EOL;

// Resolve local path like DocumentService does
if ($logoUrl) {
    $parsed = parse_url($logoUrl);
    $path = $parsed['path'] ?? '';
    if ($path) {
        $resolvedPath = public_path(ltrim($path, '/'));
        echo "Resolved local path: " . $resolvedPath . PHP_EOL;
        echo "File exists: " . (file_exists($resolvedPath) ? 'YES' : 'NO') . PHP_EOL;
    }
}

// Also check storage/app/public for logo files
echo PHP_EOL . "Checking storage/app/public for logo files:" . PHP_EOL;
$storagePublic = storage_path('app/public');
$files = glob($storagePublic . '/*.{png,jpg,jpeg,svg}', GLOB_BRACE);
foreach ($files as $f) {
    echo "  " . basename($f) . " (" . round(filesize($f)/1024, 1) . " KB)" . PHP_EOL;
}

// Check public/storage symlink
echo PHP_EOL . "Checking public/storage for logo files:" . PHP_EOL;
$pubStorage = public_path('storage');
if (is_dir($pubStorage)) {
    $files2 = glob($pubStorage . '/*.{png,jpg,jpeg}', GLOB_BRACE);
    foreach ($files2 as $f) {
        echo "  " . basename($f) . " (" . round(filesize($f)/1024, 1) . " KB)" . PHP_EOL;
    }
    // check company subfolder
    $compDir = $pubStorage . '/company';
    if (is_dir($compDir)) {
        $files3 = glob($compDir . '/*.{png,jpg,jpeg}', GLOB_BRACE);
        foreach ($files3 as $f) {
            echo "  company/" . basename($f) . " (" . round(filesize($f)/1024, 1) . " KB)" . PHP_EOL;
        }
    }
} else {
    echo "  public/storage does not exist or is not a directory" . PHP_EOL;
}
