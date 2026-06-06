<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

$employees = Employee::with(['designation', 'manager'])->get();
echo "ID | Code | Name | Designation | Position Level | Reporting To (Manager)\n";
echo str_repeat("-", 80) . "\n";
foreach ($employees as $e) {
    $mgrName = $e->manager ? $e->manager->full_name : 'None';
    $desig = $e->designation ? $e->designation->title : 'None';
    echo "{$e->id} | {$e->employee_code} | {$e->full_name} | {$desig} | {$e->position_level} | {$mgrName} (ID: " . ($e->reporting_to ?? 'null') . ")\n";
}
