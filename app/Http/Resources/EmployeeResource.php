<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_code'   => $this->employee_code,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'gender'          => $this->gender,
            'dob'             => $this->dob?->toDateString(),
            'address'         => $this->address,
            'city'            => $this->city,
            'state'           => $this->state,
            'country'         => $this->country,
            'pincode'         => $this->pincode,
            'department_id'   => $this->department_id,
            'designation_id'  => $this->designation_id,
            'department'      => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'designation'     => $this->whenLoaded('designation', fn() => [
                'id'    => $this->designation->id,
                'title' => $this->designation->title,
            ]),
            'joining_date'    => $this->joining_date?->toDateString(),
            'exit_date'       => $this->exit_date?->toDateString(),
            'employment_type' => $this->employment_type,
            'status'          => $this->status,
            'photo'           => $this->photo_url,
            // Emergency contact
            'emergency_contact_name'     => $this->emergency_contact_name,
            'emergency_contact_phone'    => $this->emergency_contact_phone,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            // Bank details
            'bank_name'           => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc'           => $this->bank_ifsc,
            'bank_branch'         => $this->bank_branch,
            'created_at'          => $this->created_at->toDateString(),
        ];
    }
}