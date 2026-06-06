<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HierarchyService;

$service = new HierarchyService();
$stats = $service->getHierarchyStats();

echo "--- HIERARCHY STATS RESULTS ---\n";
print_r($stats);
