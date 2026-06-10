<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    // Cached per-request so role check doesn't hit DB once per row in a collection
    private static ?bool $cachedIsAdmin = null;

    public function toArray(Request $request): array
    {
        $user = auth()->user();

        if (self::$cachedIsAdmin === null) {
            self::$cachedIsAdmin = $user && (
                $user->hasRole('super_admin') ||
                $user->hasRole('admin') ||
                $user->hasRole('hr')
            );
        }

        $isAdminOrHR  = self::$cachedIsAdmin;
        $isOwnProfile = $user && ($user->employee_id === $this->id || ($user->employee && $user->employee->id === $this->id));
        $showSensitive = $isAdminOrHR || $isOwnProfile;

        $res = [
            'id'              => $this->id,
            'employee_code'   => $this->employee_code,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'gender'          => $this->gender,
            'blood_group'     => $this->blood_group,
            'dob'             => $this->dob?->toDateString(),
            'address'         => $this->address,
            'city'            => $this->city,
            'state'           => $this->state,
            'country'         => $this->country,
            'pincode'         => $this->pincode,
            'department_id'   => $this->department_id,
            'designation_id'  => $this->designation_id,
            'reporting_to'    => $this->reporting_to,
            'department'      => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'designation'     => $this->whenLoaded('designation', fn() => [
                'id'    => $this->designation->id,
                'title' => $this->designation->title,
            ]),
            'manager'         => $this->whenLoaded('manager', fn() => $this->manager ? [
                'id'            => $this->manager->id,
                'full_name'     => $this->manager->full_name,
                'employee_code' => $this->manager->employee_code,
                'designation'   => $this->manager->designation?->title,
                'photo'         => $this->manager->photo_url,
            ] : null),
            'assets'          => $this->whenLoaded('assetAllocations', fn() => $this->assetAllocations->map(fn($alloc) => [
                'id' => $alloc->id,
                'asset_id' => $alloc->asset_id,
                'allocated_date' => $alloc->allocated_date?->toDateString(),
                'return_date' => $alloc->return_date?->toDateString(),
                'status' => $alloc->status,
                'condition_notes' => $alloc->condition_notes,
                'return_notes' => $alloc->return_notes,
                'asset' => $alloc->asset ? [
                    'id' => $alloc->asset->id,
                    'asset_code' => $alloc->asset->asset_code,
                    'name' => $alloc->asset->name,
                    'type' => $alloc->asset->type,
                    'brand' => $alloc->asset->brand,
                    'model' => $alloc->asset->model,
                    'serial_number' => $alloc->asset->serial_number,
                    'specifications' => $alloc->asset->specifications,
                ] : null,
            ])),
            'joining_date'    => $this->joining_date?->toDateString(),
            'exit_date'       => $this->exit_date?->toDateString(),
            'employment_type' => $this->employment_type,
            'status'          => $this->status,
            'photo'           => $this->photo_url,
            // Emergency contact
            'emergency_contact_name'     => $this->emergency_contact_name,
            'emergency_contact_phone'    => $this->emergency_contact_phone,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            'created_at'       => $this->created_at?->toDateString(),
        ];

        if ($showSensitive) {
            $res = array_merge($res, [
                // Bank details
                'bank_name'           => $this->bank_name,
                'bank_account_number' => $this->bank_account_number,
                'bank_ifsc'           => $this->bank_ifsc,
                'bank_branch'         => $this->bank_branch,
                // Salary
                'basic_salary'     => $this->basic_salary ?? 0,
                'hra'              => $this->hra ?? 0,
                'allowances'       => $this->allowances ?? [],
                'total_allowances' => $this->total_allowances ?? 0,
                'bonus'            => $this->bonus ?? 0,
                'pf_percentage'    => $this->pf_percentage ?? 0,
                'pf_deduction'     => $this->pf_deduction ?? 0,
                'esi_employee'     => $this->esi_employee ?? 0,
                'esi_employer'     => $this->esi_employer ?? 0,
                'pt_amount'        => $this->pt_amount ?? 0,
                'pt_state'         => $this->pt_state,
                'tds_amount'       => $this->tds_amount ?? 0,
                'other_deductions' => $this->other_deductions ?? 0,
                'ctc'              => $this->ctc ?? 0,
                // Documents
                'pan_number'       => $this->pan_number,
                'aadhaar_number'   => $this->aadhaar_number,
                'driving_license'  => $this->driving_license,
                'passport_number'  => $this->passport_number,
                'voter_id'         => $this->voter_id,
                'uan_number'       => $this->uan_number,
                'previous_designation' => $this->previousDesignation?->title,
                'designation_revised_date' => $this->designation_revised_date?->toDateString(),
                'previous_designation_joining_date' => $this->previous_designation_joining_date?->toDateString(),
                'official_dob'     => $this->official_dob?->toDateString(),
            ]);
        }

        return $res;
    }
}