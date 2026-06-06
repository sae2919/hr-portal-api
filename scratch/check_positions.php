<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

$employees = Employee::with('designation')->get();
foreach ($employees as $emp) {
    $designationTitle = $emp->designation?->title ?? 'N/A';
    echo "ID: {$emp->id} | Name: {$emp->first_name} {$emp->last_name} | Designation: {$designationTitle} | Position Level: " . ($emp->position_level ?? 'NULL') . "\n";
}
