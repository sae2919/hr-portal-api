<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingDocument extends Model
{
    protected $table = 'onboarding_documents';
    
    protected $fillable = [
        'onboarding_request_id', 'document_type', 'original_name',
        'file_path', 'file_size', 'mime_type', 'status',
        'verification_notes', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function onboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OnboardingRequest::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        $labels = [
            'resume' => 'Resume/CV',
            'offer_letter' => 'Offer Letter',
            'id_proof' => 'ID Proof',
            'address_proof' => 'Address Proof',
            'degree' => 'Degree Certificate',
            'previous_employment' => 'Previous Employment Proof',
            'bank_details' => 'Bank Details',
            'pan_card' => 'PAN Card',
            'aadhaar_card' => 'Aadhaar Card',
            'passport' => 'Passport',
            'other' => 'Other',
        ];
        return $labels[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}