<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'job_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'experience',
        'resume',
        'status',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}