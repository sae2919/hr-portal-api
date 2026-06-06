<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

foreach ([31, 35] as $id) {
    $emp = Employee::find($id);
    if ($emp) {
        echo "ID: {$emp->id}\n";
        echo "Name: {$emp->full_name}\n";
        echo "Designation ID: " . ($emp->designation_id ?? 'null') . "\n";
        echo "Department ID: " . ($emp->department_id ?? 'null') . "\n";
        echo "Employment Type: {$emp->employment_type}\n";
        echo "Status: {$emp->status}\n";
        echo "Reporting To: " . ($emp->reporting_to ?? 'null') . "\n";
        echo "Position Level: " . ($emp->position_level ?? 'null') . "\n";
        echo "---------------------------\n";
    }
}
