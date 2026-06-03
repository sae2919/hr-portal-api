<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'old_basic_salary',
        'old_hra',
        'old_allowances',
        'old_bonus',
        'old_gross_salary',
        'old_net_salary',
        'new_basic_salary',
        'new_hra',
        'new_allowances',
        'new_bonus',
        'new_gross_salary',
        'new_net_salary',
        'increment_percentage',
        'effective_date',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'increment_percentage' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
