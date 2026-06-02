<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLetter extends Model
{
    protected $table = 'offer_letters';
    
    protected $fillable = [
        'onboarding_request_id', 'letter_number', 'letter_date',
        'file_path', 'status', 'sent_at', 'accepted_at', 'created_by'
    ];

    protected $casts = [
        'letter_date' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($letter) {
            if (empty($letter->letter_number)) {
                $letter->letter_number = 'OL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function onboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OnboardingRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}