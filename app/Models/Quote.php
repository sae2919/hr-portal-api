<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $table = 'quotes';
    
    protected $fillable = [
        'quote', 'author', 'category', 'is_active', 'display_order', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get random active quote
    public static function getRandomQuote(): ?self
    {
        return self::where('is_active', true)
            ->inRandomOrder()
            ->first();
    }

    // Get quote of the day
    public static function getQuoteOfTheDay(): ?self
    {
        $today = now()->dayOfYear;
        $count = self::where('is_active', true)->count();
        
        if ($count === 0) return null;
        
        $index = $today % $count;
        return self::where('is_active', true)
            ->orderBy('id')
            ->skip($index)
            ->first();
    }
}