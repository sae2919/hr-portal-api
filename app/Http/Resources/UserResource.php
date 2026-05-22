<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'status'        => $this->status,
            'role'          => $this->getRoleNames()->first(),
            'roles'         => $this->getRoleNames(),
            'permissions'   => $this->getAllPermissions()->pluck('name'),
            'employee_id'   => $this->employee_id,
            'employee'      => $this->whenLoaded('employee', function () {
                return [
                    'id'            => $this->employee->id,
                    'employee_code' => $this->employee->employee_code,
                    'full_name'     => $this->employee->full_name,
                    'photo'         => $this->employee->photo_url,
                    'department'    => $this->employee->department?->name,
                    'designation'   => $this->employee->designation?->title,
                ];
            }),
            'last_login_at' => $this->last_login_at?->diffForHumans(),
            'created_at'    => $this->created_at->toDateString(),
        ];
    }
}