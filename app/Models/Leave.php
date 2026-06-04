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
        'team_lead_status', 'team_lead_id',
        'team_lead_rejection_reason', 'team_lead_acted_at', 'hr_override',
        'applied_by_admin', 'is_comp_off_claim',
    ];

    protected $casts = [
        'start_date'         => 'date',
        'end_date'           => 'date',
        'approved_at'        => 'datetime',
        'team_lead_acted_at' => 'datetime',
        'hr_override'        => 'boolean',
        'applied_by_admin'   => 'boolean',
        'is_comp_off_claim'  => 'boolean',
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

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public static function isWeekOff(Carbon $date): bool
    {
        if ($date->isSunday()) {
            return true;
        }
        if ($date->isSaturday()) {
            $weekOfMonth = (int) ceil($date->day / 7);
            return $weekOfMonth === 2 || $weekOfMonth === 4;
        }
        return false;
    }

    public static function calculateDays(string $start, string $end): float
    {
        $startDate = Carbon::parse($start);
        $endDate   = Carbon::parse($end);
        $days      = 0;

        while ($startDate->lte($endDate)) {
            if (!self::isWeekOff($startDate)) {
                $days++;
            }
            $startDate->addDay();
        }

        return $days;
    }
}