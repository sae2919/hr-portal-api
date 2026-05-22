<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date',
        'end_date', 'days', 'reason', 'status',
        'approved_by', 'rejection_reason', 'approved_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function calculateDays(string $start, string $end): float
    {
        $startDate = Carbon::parse($start);
        $endDate   = Carbon::parse($end);
        $days      = 0;

        while ($startDate->lte($endDate)) {
            if ($startDate->isWeekday()) $days++;
            $startDate->addDay();
        }

        return $days;
    }
}