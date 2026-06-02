<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingRequest extends Model
{
    protected $table = 'onboarding_requests';
    
    protected $fillable = [
        'candidate_name', 'email', 'phone', 'position', 'department',
        'joining_date', 'ctc', 'status', 'rejection_reason',
        'approved_by', 'approved_at', 'created_by'
    ];

    protected $casts = [
        'joining_date' => 'date',
        'approved_at' => 'datetime',
        'ctc' => 'decimal:2',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OnboardingDocument::class);
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(OfferLetter::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class);
    }

    public function assetAllocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'onboarded' => 'bg-blue-100 text-blue-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'onboarded' => 'Onboarded',
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }
}