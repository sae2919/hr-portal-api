<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $hr  = Department::where('code', 'HR')->first();
        $eng = Department::where('code', 'ENG')->first();
        $fin = Department::where('code', 'FIN')->first();
        $mkt = Department::where('code', 'MKT')->first();

        $hrm  = Designation::where('code', 'HRM')->first();
        $se   = Designation::where('code', 'SE')->first();
        $sse  = Designation::where('code', 'SSE')->first();
        $acc  = Designation::where('code', 'ACC')->first();
        $mktex = Designation::where('code', 'MKTEX')->first();

        $employees = [
            [
                'first_name'     => 'Priya',
                'last_name'      => 'Sharma',
                'email'          => 'priya.sharma@company.com',
                'phone'          => '9876543210',
                'gender'         => 'female',
                'dob'            => '1992-03-15',
                'department_id'  => $hr?->id,
                'designation_id' => $hrm?->id,
                'joining_date'   => '2020-01-10',
                'employment_type'=> 'full_time',
                'status'         => 'active',
                'city'           => 'Mumbai',
                'state'          => 'Maharashtra',
            ],
            [
                'first_name'     => 'Rahul',
                'last_name'      => 'Verma',
                'email'          => 'rahul.verma@company.com',
                'phone'          => '9876543211',
                'gender'         => 'male',
                'dob'            => '1995-07-22',
                'department_id'  => $eng?->id,
                'designation_id' => $se?->id,
                'joining_date'   => '2021-06-01',
                'employment_type'=> 'full_time',
                'status'         => 'active',
                'city'           => 'Bangalore',
                'state'          => 'Karnataka',
            ],
            [
                'first_name'     => 'Anjali',
                'last_name'      => 'Singh',
                'email'          => 'anjali.singh@company.com',
                'phone'          => '9876543212',
                'gender'         => 'female',
                'dob'            => '1993-11-08',
                'department_id'  => $eng?->id,
                'designation_id' => $sse?->id,
                'joining_date'   => '2019-03-15',
                'employment_type'=> 'full_time',
                'status'         => 'active',
                'city'           => 'Hyderabad',
                'state'          => 'Telangana',
            ],
            [
                'first_name'     => 'Vikram',
                'last_name'      => 'Patel',
                'email'          => 'vikram.patel@company.com',
                'phone'          => '9876543213',
                'gender'         => 'male',
                'dob'            => '1990-05-30',
                'department_id'  => $fin?->id,
                'designation_id' => $acc?->id,
                'joining_date'   => '2018-08-20',
                'employment_type'=> 'full_time',
                'status'         => 'active',
                'city'           => 'Delhi',
                'state'          => 'Delhi',
            ],
            [
                'first_name'     => 'Sneha',
                'last_name'      => 'Reddy',
                'email'          => 'sneha.reddy@company.com',
                'phone'          => '9876543214',
                'gender'         => 'female',
                'dob'            => '1997-01-25',
                'department_id'  => $mkt?->id,
                'designation_id' => $mktex?->id,
                'joining_date'   => '2022-09-01',
                'employment_type'=> 'full_time',
                'status'         => 'active',
                'city'           => 'Chennai',
                'state'          => 'Tamil Nadu',
            ],
        ];

        foreach ($employees as $emp) {
            Employee::firstOrCreate(
                ['email' => $emp['email']],
                $emp
            );
        }

        $this->command->info('Employees seeded.');
    }
}