<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payroll;
use App\Models\SalaryStructure;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'blood_group',
        'dob',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'department_id',
        'designation_id',
        'joining_date',
        'exit_date',
        'employment_type',
        'status',
        'photo',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
        'bank_branch',
    ];

    protected $casts = [
        'dob'          => 'date',
        'joining_date' => 'date',
        'exit_date'    => 'date',
    ];

    // ── Auto-generate employee code ───────────────────────────────
    protected static function booted(): void
{
    static::created(function ($employee) {

        $basic = 50000;

        $hra = $basic * 0.40;
        $allowances = 5000;
        $bonus = 2000;

        $gross = $basic + $hra + $allowances + $bonus;

        $pf = $basic * 0.12;
        $tax = $basic * 0.10;

        $net = $gross - ($pf + $tax);

        // ====================================
        // Create Salary Structure
        // ====================================

        $salary = SalaryStructure::create([

            'employee_id' => $employee->id,

            'basic_salary' => $basic,

            'hra' => $hra,

            'allowances' => $allowances,

            'bonus' => $bonus,

            'pf_deduction' => $pf,

            'tax_deduction' => $tax,

            'other_deductions' => 0,

            'gross_salary' => $gross,

            'net_salary' => $net,

            'effective_from' => now(),
        ]);

        // ====================================
        // Create Payroll
        // ====================================

        Payroll::create([

            'employee_id' => $employee->id,

            'salary_structure_id' => $salary->id,

            'month' => now()->month,

            'year' => now()->year,

            'working_days' => 22,

            'present_days' => 22,

            'leave_days' => 0,

            'gross_salary' => $gross,

            'total_deductions' => $pf + $tax,

            'net_salary' => $net,

            'status' => 'processed',

            'processed_at' => now(),
        ]);
    });
}

    private static function generateCode(): string
    {
        $last = self::latest('id')->first();
        $next = $last ? (int) substr($last->employee_code, 3) + 1 : 1;
        return 'EMP' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ─────────────────────────────────────────────

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;
        return asset('storage/' . $this->photo);
    }
}