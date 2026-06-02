<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    protected $table = 'onboarding_tasks';
    
    protected $fillable = [
        'onboarding_request_id', 'task_name', 'assigned_to', 'description',
        'due_date', 'status', 'completion_notes', 'completed_by', 'completed_at'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function onboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OnboardingRequest::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getAssignedToLabelAttribute(): string
    {
        $labels = [
            'HR' => 'Human Resources',
            'IT' => 'IT Department',
            'Admin' => 'Administration',
            'Manager' => 'Hiring Manager',
        ];
        return $labels[$this->assigned_to] ?? $this->assigned_to;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}