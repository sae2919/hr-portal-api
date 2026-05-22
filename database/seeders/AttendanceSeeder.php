<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $today     = Carbon::today();

        foreach ($employees as $employee) {
            // Seed last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date   = $today->copy()->subDays($i);
                if ($date->isWeekend()) continue;

                $statuses = ['present', 'present', 'present', 'present', 'late', 'present'];
                $status   = $statuses[array_rand($statuses)];

                Attendance::firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'check_in'  => $status === 'late' ? '10:15' : '09:00',
                        'check_out' => '18:00',
                        'status'    => $status,
                    ]
                );
            }
        }

        $this->command->info('Attendance seeded for last 7 days.');
    }
}