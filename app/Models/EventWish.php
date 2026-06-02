<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventWish extends Model
{
    protected $fillable = [
        'employee_id',
        'sender_id',
        'wish_type',
        'message',
        'emoji',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // The employee being wished
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // The employee who sent the wish
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sender_id');
    }
}