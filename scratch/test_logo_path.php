<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\CompanySetting;

$logoUrl = CompanySetting::where('key', 'company_logo')->value('value');
echo "Logo URL from DB: $logoUrl\n";

if ($logoUrl) {
    $parsed = parse_url($logoUrl);
    $path = $parsed['path'] ?? '';
    echo "Path from URL: $path\n";
    
    // Test public_path
    $publicPath = public_path(substr($path, 1));
    echo "Public Path: $publicPath\n";
    echo "Exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";
    
    // Test storage_path
    if (str_starts_with($path, '/storage/')) {
        $storagePath = storage_path('app/public/' . substr($path, 9));
        echo "Storage Path: $storagePath\n";
        echo "Exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
    }
}
