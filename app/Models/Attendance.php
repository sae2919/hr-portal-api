<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'overtime_hours',
        'note',
    ];

    protected $casts = [
        'date'           => 'date',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getWorkedHoursAttribute(): ?string
    {
        if (!$this->check_in || !$this->check_out) return null;
        $in  = \Carbon\Carbon::parse($this->check_in);
        $out = \Carbon\Carbon::parse($this->check_out);
        $diff = $in->diff($out);
        return sprintf('%02d:%02d', $diff->h, $diff->i);
    }
}