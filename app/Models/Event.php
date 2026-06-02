<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $table = 'events';
    
    protected $fillable = [
        'title', 'description', 'type', 'event_date', 'end_date',
        'start_time', 'end_time', 'location', 'attendees', 'departments',
        'is_recurring', 'recurrence_pattern', 'color', 'icon', 'status', 'created_by'
    ];

    protected $casts = [
        'event_date' => 'date',
        'end_date' => 'date',
        'attendees' => 'array',
        'departments' => 'array',
        'is_recurring' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'holiday' => 'Holiday',
            'birthday' => 'Birthday',
            'company_event' => 'Company Event',
            'meeting' => 'Meeting',
            'training' => 'Training',
            'other' => 'Other',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getTypeIconAttribute(): string
    {
        $icons = [
            'holiday' => '🎉',
            'birthday' => '🎂',
            'company_event' => '🏢',
            'meeting' => '📅',
            'training' => '📚',
            'other' => '📌',
        ];
        return $icons[$this->type] ?? '📅';
    }
}