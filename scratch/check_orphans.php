<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

echo "--- ORPHAN EMPLOYEES (reporting_to is null) ---\n";
foreach (Employee::whereNull('reporting_to')->get() as $e) {
    echo "ID: {$e->id} | Name: {$e->full_name} | Designation: " . ($e->designation?->title ?? 'N/A') . " | Position Level: {$e->position_level}\n";
}
