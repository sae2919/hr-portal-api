<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        static::creating(function (Employee $employee) {
            if (empty($employee->employee_code)) {
                $employee->employee_code = self::generateCode();
            }
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