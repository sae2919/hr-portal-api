<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'hra',
        'allowances',
        'bonus',
        'pf_deduction',
        'tax_deduction',
        'other_deductions',
        'gross_salary',
        'net_salary',
        'effective_from',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}