<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get a payroll record to use for test
$payroll = App\Models\Payroll::with(['employee', 'items', 'salaryStructure'])->latest()->first();

if (!$payroll) {
    echo "No payroll records found!" . PHP_EOL;
    exit(1);
}

echo "Using payroll: ID=" . $payroll->id . " Employee=" . $payroll->employee->first_name . " " . $payroll->employee->last_name . PHP_EOL;

$vars = App\Services\DocumentService::getPayslipVariables($payroll);
$pdf = App\Services\DocumentService::render('monthly_payslip_template', $vars);

$outputPath = storage_path('app/public/test_payslip_header.pdf');
file_put_contents($outputPath, $pdf->output());
echo "Payslip PDF generated: storage/app/public/test_payslip_header.pdf" . PHP_EOL;
echo "Full path: " . $outputPath . PHP_EOL;
