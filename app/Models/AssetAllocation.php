<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAllocation extends Model
{
    protected $table = 'asset_allocations';
    
    protected $fillable = [
        'asset_id', 'employee_id', 'onboarding_request_id', 'allocated_date',
        'return_date', 'status', 'condition_notes', 'return_notes', 'allocated_by',
        'charger_given', 'sim_given'
    ];

    protected $casts = [
        'allocated_date' => 'date',
        'return_date' => 'date',
        'charger_given' => 'boolean',
        'sim_given' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function onboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OnboardingRequest::class);
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}