<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Http\Controllers\Api\PayrollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 1. Get Admin User
$admin = User::where('email', 'admin@hrportal.com')->first();
if (!$admin) {
    echo "Admin user not found!\n";
    exit(1);
}
auth()->login($admin);

// 2. Get or Create a test employee
$employee = Employee::first();
if (!$employee) {
    echo "No employee found in database to run test!\n";
    exit(1);
}

echo "Running payroll test for Employee: {$employee->full_name}\n";

// Save active salary structure to restore later
$oldSalary = SalaryStructure::where('employee_id', $employee->id)->where('status', 'active')->first();
if ($oldSalary) {
    $oldSalary->status = 'inactive';
    $oldSalary->save();
}

// Create a mock salary structure (Gross: 25,000 | Basic: 12,500 | HRA: 5,000 | Allowances: 7,500)
$mockSalary = SalaryStructure::create([
    'employee_id' => $employee->id,
    'basic_salary' => 12500,
    'hra' => 5000,
    'allowances' => 7500,
    'bonus' => 0,
    'pf_deduction' => 1500,
    'tax_deduction' => 200,
    'other_deductions' => 0,
    'gross_salary' => 25000,
    'net_salary' => 23300,
    'effective_from' => now()->subMonth(),
    'status' => 'active'
]);

// 3. Clear any existing payroll for May 2026
DB::table('payrolls')->where('employee_id', $employee->id)->where('month', 5)->where('year', 2026)->delete();

// We want 4 LOP days.
// Let's see how many working days there are in May 2026.
$startOfMonth = \Carbon\Carbon::create(2026, 5, 1)->startOfMonth();
$endOfMonth   = \Carbon\Carbon::create(2026, 5, 1)->endOfMonth();
$workingDays = 0;
for ($d = $startOfMonth->copy(); $d->lte($endOfMonth); $d->addDay()) {
    if (!\App\Models\Leave::isWeekOff($d)) $workingDays++;
}

// To get exactly 4 LOP days, the accounted/present days should be: $workingDays - 4
$targetPresentDays = $workingDays - 4;

echo "May 2026 Working Days (excluding weekends): {$workingDays}\n";
echo "Simulating Present Days: {$targetPresentDays} (resulting in 4 LOP days)\n";

// Clear existing attendance for May 2026
DB::table('attendances')->where('employee_id', $employee->id)->whereMonth('date', 5)->whereYear('date', 2026)->delete();

// Seed attendance to get exactly $targetPresentDays present days
for ($i = 0; $i < $targetPresentDays; $i++) {
    // Find the next non-weekend day
    $date = $startOfMonth->copy();
    $added = 0;
    while ($added <= $i) {
        if (!\App\Models\Leave::isWeekOff($date)) {
            if ($added == $i) {
                DB::table('attendances')->insert([
                    'employee_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'status' => 'present',
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
            $added++;
        }
        $date->addDay();
    }
}

// Clear any leaves
DB::table('leaves')->where('employee_id', $employee->id)->delete();

// 4. Trigger generate controller method
$request = Request::create('/api/v1/payrolls/generate', 'POST', [
    'employee_id' => $employee->id,
    'month' => 5,
    'year' => 2026,
    'include_pf' => true,
    'pf_percentage' => 12,
    'include_pt' => true,
    'pt_amount' => 200
]);

$controller = app(PayrollController::class);
try {
    $response = $controller->generate($request);
    $data = json_decode($response->getContent(), true);

    // Detailed items
    $payroll = \App\Models\Payroll::with('items')->where('employee_id', $employee->id)->where('month', 5)->where('year', 2026)->first();
    
    $daysInMonth = 31; // May has 31 days
    echo "\n--- CALCULATION RESULTS ---\n";
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Month/Year: " . $data['month'] . "/" . $data['year'] . "\n";
    echo "Working Days (Calendar Days): " . $data['working_days'] . " (Expected: 31)\n";
    echo "Present Days (Paid Days): " . $data['present_days'] . " (Expected: 27)\n";
    echo "LOP Days: " . $data['lop_days'] . " (Expected: 4)\n";
    echo "Base Gross Salary: ₹25,000.00\n";
    echo "Calculated LOP Deduction (Gross-Based): ₹" . $payroll->lop_deduction . " (Expected: 25000 * 4 / 31 = ₹" . round(25000 * 4 / 31, 2) . ")\n";
    echo "Total Deductions: ₹" . $data['total_deductions'] . " (Expected: PF + PT = 1306.45 + 200.00 = 1506.45)\n";
    echo "Net Salary: ₹" . $data['net_salary'] . " (Expected: Revised Gross - Deductions = 21774.19 - 1506.45 = 20267.74)\n";
    
    echo "\nPayroll Items stored in database:\n";
    foreach ($payroll->items as $item) {
        echo "- " . $item->name . " [" . $item->type . "]: ₹" . $item->amount . "\n";
    }

} catch (\Exception $e) {
    echo "Error running generator: " . $e->getMessage() . "\n";
} finally {
    // Restore old salary structure
    $mockSalary->delete();
    if ($oldSalary) {
        $oldSalary->status = 'active';
        $oldSalary->save();
    }
    // Clean up test data
    DB::table('payrolls')->where('employee_id', $employee->id)->where('month', 5)->where('year', 2026)->delete();
    DB::table('attendances')->where('employee_id', $employee->id)->whereMonth('date', 5)->whereYear('date', 2026)->delete();
}
