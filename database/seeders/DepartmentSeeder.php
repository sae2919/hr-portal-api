<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources',   'code' => 'HR',  'description' => 'Manages people operations'],
            ['name' => 'Engineering',        'code' => 'ENG', 'description' => 'Software development team'],
            ['name' => 'Finance',            'code' => 'FIN', 'description' => 'Financial operations'],
            ['name' => 'Marketing',          'code' => 'MKT', 'description' => 'Brand and growth'],
            ['name' => 'Operations',         'code' => 'OPS', 'description' => 'Business operations'],
            ['name' => 'Sales',              'code' => 'SAL', 'description' => 'Revenue generation'],
            ['name' => 'Customer Support',   'code' => 'CS',  'description' => 'Customer success'],
            ['name' => 'Product Management', 'code' => 'PM',  'description' => 'Product strategy'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['name' => $dept['name']],
                array_merge($dept, ['status' => 'active'])
            );
        }

        $this->command->info('Departments seeded.');
    }
}