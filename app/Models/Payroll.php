<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_structure_id',
        'month',
        'year',
        'working_days',
        'present_days',
        'leave_days',
        'lop_days',
        'lop_deduction',
        'basic_salary',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'status',
        'processed_at',
        'paid_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}