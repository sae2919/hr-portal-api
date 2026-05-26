<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        foreach ($departments as $department) {

            $job = Job::create([
                'title' => $department->name . ' Executive',
                'department_id' => $department->id,
                'description' => 'Hiring for ' . $department->name,
                'vacancies' => 3,
                'salary_from' => 40000,
                'salary_to' => 90000,
                'status' => 'open',
                'deadline' => now()->addDays(30),
            ]);

            for ($i = 1; $i <= 2; $i++) {

                $candidate = Candidate::create([
                    'job_id' => $job->id,
                    'first_name' => 'Candidate',
                    'last_name' => $i,
                    'email' => 'candidate'.$department->id.$i.'@mail.com',
                    'phone' => '9999999999',
                    'experience' => rand(1, 6),
                    'status' => 'interview',
                ]);

                Interview::create([
                    'candidate_id' => $candidate->id,
                    'interview_date' => now()->addDays(5),
                    'mode' => 'Online',
                    'interviewer' => 'HR Manager',
                    'feedback' => 'Good communication skills',
                    'result' => 'pending',
                ]);
            }
        }
    }
}