<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $table = 'assets';
    
    protected $fillable = [
        'asset_code', 'name', 'type', 'brand', 'model', 'serial_number',
        'color', 'purchase_date', 'purchase_price', 'status',
        'specifications', 'image_path'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = 'AST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function currentAllocation()
    {
        return $this->hasOne(AssetAllocation::class)->where('status', 'allocated')->latest();
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'available' => 'bg-green-100 text-green-800',
            'assigned' => 'bg-blue-100 text-blue-800',
            'maintenance' => 'bg-yellow-100 text-yellow-800',
            'scrapped' => 'bg-red-100 text-red-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'laptop' => 'Laptop',
            'monitor' => 'Monitor',
            'phone' => 'Phone',
            'keyboard' => 'Keyboard',
            'mouse' => 'Mouse',
            'headset' => 'Headset',
            'docking_station' => 'Docking Station',
            'other' => 'Other',
        ];
        return $labels[$this->type] ?? ucfirst($this->type);
    }
}