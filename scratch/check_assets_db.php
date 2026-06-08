<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asset;
use App\Models\AssetAllocation;

echo "--- Assets ---\n";
foreach (Asset::all() as $asset) {
    echo "ID: {$asset->id}, Code: {$asset->asset_code}, Name: {$asset->name}, Status: {$asset->status}\n";
}

echo "\n--- Asset Allocations ---\n";
foreach (AssetAllocation::all() as $alloc) {
    echo "ID: {$alloc->id}, Asset ID: {$alloc->asset_id}, Employee ID: " . ($alloc->employee_id ?? 'NULL') . ", Onboarding Request ID: " . ($alloc->onboarding_request_id ?? 'NULL') . ", Status: {$alloc->status}\n";
}
