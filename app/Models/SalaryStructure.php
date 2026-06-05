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

    protected static function booted(): void
    {
        static::saved(function ($structure) {
            if ($structure->status === 'active') {
                \Illuminate\Support\Facades\DB::table('employees')
                    ->where('id', $structure->employee_id)
                    ->update([
                        'basic_salary'     => $structure->basic_salary,
                        'hra'              => $structure->hra,
                        'allowances'       => json_encode([['type' => 'other', 'amount' => $structure->allowances]]),
                        'bonus'            => $structure->bonus,
                        'pf_deduction'     => $structure->pf_deduction,
                        'tds_amount'       => $structure->tax_deduction,
                        'other_deductions' => $structure->other_deductions,
                        'ctc'              => $structure->gross_salary * 12,
                    ]);
            }
        });
    }
}