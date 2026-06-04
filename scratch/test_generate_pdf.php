<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;

$payroll = Payroll::with(['employee.department', 'salaryStructure', 'items'])->orderBy('id', 'desc')->first();
if ($payroll) {
    echo "Compiling payslip for ID: {$payroll->id}, Employee: {$payroll->employee->full_name}...\n";
    try {
        $pdf = Pdf::loadView('pdf.payslip', [
            'payroll' => $payroll,
            'employee' => $payroll->employee
        ]);
        
        $destPath = __DIR__ . '/../public/test_payslip_compiled.pdf';
        file_put_contents($destPath, $pdf->output());
        echo "Successfully generated PDF: $destPath\n";
    } catch (\Exception $e) {
        echo "Error generating PDF: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No payroll record found\n";
}
