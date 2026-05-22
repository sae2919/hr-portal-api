<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            // Engineering
            ['title' => 'Software Engineer',        'code' => 'SE'],
            ['title' => 'Senior Software Engineer',  'code' => 'SSE'],
            ['title' => 'Tech Lead',                 'code' => 'TL'],
            ['title' => 'Engineering Manager',       'code' => 'EM'],
            // HR
            ['title' => 'HR Executive',              'code' => 'HRE'],
            ['title' => 'HR Manager',                'code' => 'HRM'],
            ['title' => 'Recruiter',                 'code' => 'REC'],
            // Finance
            ['title' => 'Accountant',                'code' => 'ACC'],
            ['title' => 'Finance Manager',           'code' => 'FM'],
            // Management
            ['title' => 'Product Manager',           'code' => 'PM'],
            ['title' => 'Team Lead',                 'code' => 'TLEAD'],
            ['title' => 'Director',                  'code' => 'DIR'],
            ['title' => 'CEO',                       'code' => 'CEO'],
            // Sales & Marketing
            ['title' => 'Sales Executive',           'code' => 'SALEX'],
            ['title' => 'Marketing Executive',       'code' => 'MKTEX'],
            // Support
            ['title' => 'Customer Support Executive','code' => 'CSE'],
        ];

        foreach ($designations as $d) {
            Designation::firstOrCreate(
                ['title' => $d['title']],
                array_merge($d, ['status' => 'active'])
            );
        }

        $this->command->info('Designations seeded.');
    }
}