<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Casual Leave',    'code' => 'CL',  'days_per_year' => 12, 'is_paid' => true,  'color' => '#3B82F6'],
            ['name' => 'Sick Leave',      'code' => 'SL',  'days_per_year' => 10, 'is_paid' => true,  'color' => '#EF4444'],
            ['name' => 'Earned Leave',    'code' => 'EL',  'days_per_year' => 15, 'is_paid' => true,  'color' => '#10B981', 'carry_forward' => true],
            ['name' => 'Unpaid Leave',    'code' => 'UL',  'days_per_year' => 30, 'is_paid' => false, 'color' => '#6B7280'],
            ['name' => 'Maternity Leave', 'code' => 'ML',  'days_per_year' => 90, 'is_paid' => true,  'color' => '#EC4899'],
            ['name' => 'Compensatory',   'code' => 'COM', 'days_per_year' => 0,  'is_paid' => true,  'color' => '#F59E0B'],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(
                ['code' => $type['code']],
                array_merge($type, [
                    'carry_forward' => $type['carry_forward'] ?? false,
                    'status' => 'active',
                ])
            );
        }

        // Initialize balances for current year
        $year  = Carbon::now()->year;
        $allTypes = LeaveType::all();
        $employees = Employee::where('status', 'active')->get();

        foreach ($employees as $emp) {
            foreach ($allTypes as $lt) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $emp->id, 'leave_type_id' => $lt->id, 'year' => $year],
                    [
                        'total_days'     => $lt->days_per_year,
                        'used_days'      => 0,
                        'remaining_days' => $lt->days_per_year,
                    ]
                );
            }
        }

        $this->command->info('Leave types and balances seeded.');
    }
}