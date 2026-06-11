<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\Payroll::with('employee')->get() as $p) {
    if (!$p->employee) {
        echo "Payroll ID {$p->id} has no employee!\n";
        continue;
    }
    echo "ID: {$p->id} | Emp: {$p->employee->first_name} {$p->employee->last_name} ({$p->employee->employment_type}) | Month: {$p->month}/{$p->year}\n";
}
