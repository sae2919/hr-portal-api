<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'title',
        'department_id',
        'description',
        'vacancies',
        'salary_from',
        'salary_to',
        'status',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }
}