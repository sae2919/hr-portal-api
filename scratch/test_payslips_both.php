<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Services\DocumentService;

$payrolls = [
    'full_time' => Payroll::find(14),
    'intern'    => Payroll::find(15)
];

foreach ($payrolls as $type => $payroll) {
    if (!$payroll) {
        echo "Payroll not found for {$type}!\n";
        continue;
    }
    echo "Generating payslip for {$type}: ID={$payroll->id} Employee={$payroll->employee->first_name}...\n";
    $vars = DocumentService::getPayslipVariables($payroll);
    $pdf = DocumentService::render('monthly_payslip_template', $vars);
    $destPath = __DIR__ . "/test_payslip_{$type}.pdf";
    file_put_contents($destPath, $pdf->output());
    echo "Saved to {$destPath}\n";
}
