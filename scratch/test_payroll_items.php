<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\Payroll;

$payroll = Payroll::with('items')->orderBy('id', 'desc')->first();
if ($payroll) {
    echo "Payroll ID: {$payroll->id}, Employee: {$payroll->employee->full_name}\n";
    echo "Month/Year: {$payroll->month}/{$payroll->year}\n";
    echo "Basic: {$payroll->basic_salary}, Gross: {$payroll->gross_salary}, Net: {$payroll->net_salary}\n";
    echo "\nItems:\n";
    foreach ($payroll->items as $item) {
        echo "  - Name: {$item->name}, Type: {$item->type}, Amount: {$item->amount}\n";
    }
} else {
    echo "No payroll record found\n";
}
