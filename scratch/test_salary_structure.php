<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$employees = App\Models\Employee::all();
echo "Total Employees: " . $employees->count() . "\n";

$migratedCount = 0;
foreach ($employees as $emp) {
    $hasStructure = App\Models\SalaryStructure::where('employee_id', $emp->id)->exists();
    if (!$hasStructure) {
        $emp->save(); // This will trigger the new saved model event and create the SalaryStructure record
        $migratedCount++;
        echo "Initialized Salary Structure for: {$emp->first_name} {$emp->last_name} (Basic: {$emp->basic_salary})\n";
    }
}
echo "Total migrated: $migratedCount\n";
