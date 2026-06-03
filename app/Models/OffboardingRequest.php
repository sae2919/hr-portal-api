<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingRequest extends Model
{
    use HasFactory;

    protected $table = 'offboarding_requests';

    protected $fillable = [
        'employee_id',
        'resignation_date',
        'last_working_day',
        'reason',
        'status',
        'rejection_reason',
        'tasks',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_day' => 'date',
        'tasks' => 'array',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
