<?php

// Require autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

// Find employee
$employee = Employee::where('first_name', 'like', '%Sailinga%')
    ->orWhere('last_name', 'like', '%Sailinga%')
    ->first();

if (!$employee) {
    echo "Employee matching 'Sailinga' not found!\n";
    exit(1);
}

echo "Found Employee: {$employee->full_name} (ID: {$employee->id})\n";

// Find June 2026 payroll
$payroll = Payroll::where('employee_id', $employee->id)
    ->where('month', 6)
    ->where('year', 2026)
    ->first();

if (!$payroll) {
    echo "Payroll record for June 2026 not found for this employee!\n";
    exit(0);
}

echo "Found Payroll Record ID: {$payroll->id} (Status: {$payroll->status}, Net Salary: ₹{$payroll->net_salary})\n";

// Delete payroll and its items
DB::transaction(function () use ($payroll) {
    $payroll->items()->delete();
    $payroll->delete();
});

echo "Payroll record deleted successfully!\n";
