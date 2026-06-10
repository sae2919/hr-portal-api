<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\Attendance;

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
        'reporting_to',
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
        // Salary
        'basic_salary',
        'hra',
        'allowances',      // ✅ Now JSON array
        'bonus',           // ✅ New field
        'pf_percentage',
        'pf_deduction',
        'esi_employee',
        'esi_employer',
        'pt_amount',
        'pt_state',
        'tds_amount',
        'other_deductions',
        'ctc',
        // Documents
        'pan_number',
        'aadhaar_number',
        'driving_license',
        'passport_number',
        'voter_id',
        'uan_number',
        'previous_designation_id',
        'official_dob',
    ];

    protected $casts = [
        'dob'          => 'date',
        'official_dob' => 'date',
        'joining_date' => 'date',
        'exit_date'    => 'date',
        'allowances'   => 'array',  // ✅ Cast allowances as JSON array
    ];

    // Pass salary data from controller
    public ?array $_salary_data = null;

    // ── Auto-generate employee code + salary/payroll on create ────
    protected static function booted(): void
    {
        static::creating(function ($employee) {
            if (empty($employee->employee_code)) {
                $employee->employee_code = self::generateCode($employee);
            }
        });


        static::saved(function ($employee) {
            // Whenever an employee is saved (created or updated), we ensure they have an active SalaryStructure matching their current employee salary fields
            
            // 1. Calculate total allowances
            $allowancesData = $employee->allowances;
            $totalAllowances = is_array($allowancesData) 
                ? collect($allowancesData)->sum('amount') 
                : (is_numeric($allowancesData) ? (float) $allowancesData : 0);

            $gross = ($employee->basic_salary ?? 0)
                   + ($employee->hra ?? 0)
                   + $totalAllowances
                   + ($employee->bonus ?? 0);

            $deductions = ($employee->pf_deduction ?? 0)
                        + ($employee->tds_amount ?? 0)
                        + ($employee->esi_employee ?? 0)
                        + ($employee->pt_amount ?? 0)
                        + ($employee->other_deductions ?? 0);

            $net = $gross - $deductions;

            // 2. Deactivate any existing salary structures for this employee
            \App\Models\SalaryStructure::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            // 3. Create the new active one
            $salary = \App\Models\SalaryStructure::create([
                'employee_id'      => $employee->id,
                'basic_salary'     => $employee->basic_salary ?? 0,
                'hra'              => $employee->hra ?? 0,
                'allowances'       => $totalAllowances, // Store the decimal sum in the decimal column
                'bonus'            => $employee->bonus ?? 0,
                'pf_deduction'     => $employee->pf_deduction ?? 0,
                'tax_deduction'    => $employee->tds_amount ?? 0,
                'other_deductions' => $employee->other_deductions ?? 0,
                'gross_salary'     => $gross,
                'net_salary'       => $net,
                'effective_from'   => now(),
                'status'           => 'active',
            ]);

            // Save the active salary structure ID as a temporary property for static::created to access
            $employee->_active_salary_structure = $salary;
        });

        static::created(function ($employee) {
            // Initialize default leave balances for the current year
            $year = now()->year;
            $types = \App\Models\LeaveType::where('status', 'active')->get();
            foreach ($types as $type) {
                $exists = \App\Models\LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $year)
                    ->exists();

                if (!$exists) {
                    \App\Models\LeaveBalance::create([
                        'employee_id'    => $employee->id,
                        'leave_type_id'  => $type->id,
                        'year'           => $year,
                        'total_days'     => (float) $type->days_per_year,
                        'used_days'      => 0,
                        'remaining_days' => (float) $type->days_per_year,
                    ]);
                }
            }

            // Create initial payroll record if _salary_data was provided (i.e. created from form)
            if (!$employee->_salary_data) return;

            $salary = $employee->_active_salary_structure;
            if (!$salary) return;

            $d = $employee->_salary_data;
            $totalAllowances = is_array($d['allowances']) 
                ? collect($d['allowances'])->sum('amount') 
                : ($d['allowances'] ?? 0);

            $gross = ($d['basic_salary'] ?? 0)
                   + ($d['hra'] ?? 0)
                   + $totalAllowances
                   + ($d['bonus'] ?? 0);

            $deductions = ($d['pf_deduction'] ?? 0)
                        + ($d['tds_amount'] ?? 0)
                        + ($d['esi_employee'] ?? 0)
                        + ($d['pt_amount'] ?? 0)
                        + ($d['other_deductions'] ?? 0);

            $net = $gross - $deductions;

            Payroll::create([
                'employee_id'         => $employee->id,
                'salary_structure_id' => $salary->id,
                'month'               => now()->month,
                'year'                => now()->year,
                'working_days'        => 22,
                'present_days'        => 22,
                'leave_days'          => 0,
                'gross_salary'        => $gross,
                'total_deductions'    => $deductions,
                'net_salary'          => $net,
                'status'              => 'processed',
                'processed_at'        => now(),
            ]);
        });
    }

    private static function generateCode(Employee $employee): string
    {
        // Check if it's a free intern (intern with 0 basic salary)
        $isFreeIntern = ($employee->employment_type === 'intern' && (float)($employee->basic_salary ?? 0) == 0);

        if ($isFreeIntern) {
            // Find the maximum numeric suffix among E codes (like E001, E002, etc.)
            $maxNum = self::where('employee_code', 'like', 'E%')
                ->pluck('employee_code')
                ->map(function ($code) {
                    return (int) substr($code, 1);
                })
                ->max();

            $next = $maxNum ? $maxNum + 1 : 1;
            return 'E' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } else {
            // Standard numeric codes (e.g. 1026, 1030)
            $maxNum = self::pluck('employee_code')
                ->map(function ($code) {
                    return is_numeric($code) ? (int) $code : 0;
                })
                ->max();

            $next = $maxNum ? $maxNum + 1 : 1000;
            return str_pad($next, 4, '0', STR_PAD_LEFT);
        }
    }

    // ── Relationships ─────────────────────────────────────────────
    public function department() { return $this->belongsTo(Department::class); }
    public function designation() { return $this->belongsTo(Designation::class); }
    public function previousDesignation() { return $this->belongsTo(Designation::class, 'previous_designation_id'); }
    public function user() { return $this->hasOne(User::class); }
    public function manager() { return $this->belongsTo(Employee::class, 'reporting_to'); }
    public function subordinates() { return $this->hasMany(Employee::class, 'reporting_to'); }
    public function salaryStructure() { return $this->hasOne(SalaryStructure::class)->latestOfMany(); }
    public function payrolls() { return $this->hasMany(Payroll::class); }
    public function leaves() { return $this->hasMany(Leave::class); }
    public function leaveBalances() { return $this->hasMany(LeaveBalance::class); }
    public function assetAllocations() { return $this->hasMany(AssetAllocation::class); }
    public function salaryRevisions() { return $this->hasMany(SalaryRevision::class); }

    // ── Accessors ─────────────────────────────────────────────────
    public function getDesignationRevisedDateAttribute(): ?\Carbon\Carbon
    {
        $revisions = $this->salaryRevisions;
        if ($revisions->isEmpty()) {
            return $this->joining_date;
        }

        $reversedRevisions = $revisions->sortBy('effective_date')->reverse();
        $currentDesignationId = $this->designation_id;
        $startDate = $this->joining_date;

        foreach ($reversedRevisions as $rev) {
            if ($rev->new_designation_id == $currentDesignationId) {
                $startDate = $rev->effective_date;
            } else {
                break;
            }
        }

        return $startDate;
    }

    public function getPreviousDesignationJoiningDateAttribute(): ?\Carbon\Carbon
    {
        $previousDesignationId = $this->previous_designation_id;
        if (!$previousDesignationId) {
            return null;
        }

        $revisions = $this->salaryRevisions;
        $reversedRevisions = $revisions->sortBy('effective_date')->reverse();
        $startDate = $this->joining_date;
        $foundSpell = false;

        foreach ($reversedRevisions as $rev) {
            if ($rev->new_designation_id == $previousDesignationId) {
                $startDate = $rev->effective_date;
                $foundSpell = true;
            } elseif ($foundSpell) {
                break;
            }
        }

        return $startDate;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    public function attendances()
{
    return $this->hasMany(Attendance::class);
}
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;
        return asset('storage/' . $this->photo);
    }

    // ✅ Helper: Get total allowances amount
    public function getTotalAllowancesAttribute(): float
    {
        if (!is_array($this->allowances)) return 0;
        return collect($this->allowances)->sum('amount');
    }
   


public function getSuperiors(): \Illuminate\Support\Collection
{
    $superiors = collect();
    $current = $this;
    
    while ($current->reporting_to) {
        $current = $current->manager;
        if ($current) {
            $superiors->push($current);
        }
    }
    
    return $superiors;
}

/**
 * Get all subordinates (recursive)
 */
public function getAllSubordinates(): \Illuminate\Support\Collection
{
    $subordinates = collect();
    
    foreach ($this->subordinates as $subordinate) {
        $subordinates->push($subordinate);
        $subordinates = $subordinates->merge($subordinate->getAllSubordinates());
    }
    
    return $subordinates;
}

/**
 * Get direct subordinates only
 */
public function getDirectSubordinates(): \Illuminate\Support\Collection
{
    return $this->subordinates;
}

/**
 * Check if employee is a manager
 */
public function isManager(): bool
{
    $managerLevels = ['manager', 'senior_manager', 'director', 'vp', 'c_level'];
    return in_array($this->position_level ?? 'staff', $managerLevels);
}

/**
 * Check if this employee can manage another employee
 */
public function canManage(Employee $employee): bool
{
    if ($this->id === $employee->id) return true;
    
    $current = $employee;
    while ($current && $current->reporting_to) {
        if ($current->reporting_to == $this->id) {
            return true;
        }
        $current = $current->manager;
    }
    
    return false;
}

/**
 * Get team size (total subordinates)
 */
public function getTeamSize(): int
{
    return $this->getAllSubordinates()->count();
}

/**
 * Update hierarchy path (call after saving reporting structure)
 */
public function updateHierarchyPath(): void
{
    $path = [];
    $current = $this;
    
    while ($current->reporting_to) {
        $path[] = $current->reporting_to;
        $current = $current->manager;
    }
    
    $this->hierarchy_path = implode('/', array_reverse($path));
    $this->saveQuietly();
}

/**
 * Get position level badge color
 */
public function getPositionBadgeColor(): string
{
    $colors = [
        'c_level' => 'bg-purple-100 text-purple-800',
        'vp' => 'bg-indigo-100 text-indigo-800',
        'director' => 'bg-blue-100 text-blue-800',
        'senior_manager' => 'bg-cyan-100 text-cyan-800',
        'manager' => 'bg-emerald-100 text-emerald-800',
        'team_lead' => 'bg-amber-100 text-amber-800',
        'staff' => 'bg-slate-100 text-slate-700',
        'intern' => 'bg-slate-50 text-slate-500',
    ];
    return $colors[$this->position_level ?? 'staff'] ?? $colors['staff'];
}

/**
 * Get position level label
 */
public function getPositionLabel(): string
{
    $labels = [
        'c_level' => 'C-Level Executive',
        'vp' => 'Vice President',
        'director' => 'Director',
        'senior_manager' => 'Senior Manager',
        'manager' => 'Manager',
        'team_lead' => 'Team Lead',
        'staff' => 'Staff',
        'intern' => 'Intern',
    ];
    return $labels[$this->position_level ?? 'staff'] ?? 'Staff';
}
}