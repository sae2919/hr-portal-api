<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $fillable = [
        'candidate_id',
        'interview_date',
        'mode',
        'interviewer',
        'feedback',
        'result',
    ];

    protected $casts = [
        'interview_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}