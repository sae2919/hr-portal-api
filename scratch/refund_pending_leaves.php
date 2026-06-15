<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

DB::transaction(function () {
    $pendingLeaves = DB::table('leaves')
        ->where('status', 'pending')
        ->where('is_comp_off_claim', false)
        ->get();
        
    echo "Found " . $pendingLeaves->count() . " pending leaves to refund.\n";
    
    foreach ($pendingLeaves as $leave) {
        $year = Carbon::parse($leave->start_date)->year;
        
        echo "Refunding employee ID {$leave->employee_id} for leave ID {$leave->id} ({$leave->days} days, year {$year})...\n";
        
        DB::table('leave_balances')
            ->where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', $year)
            ->decrement('used_days', $leave->days);
            
        DB::table('leave_balances')
            ->where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', $year)
            ->increment('remaining_days', $leave->days);
    }
});

echo "Done!\n";
