<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveBalancesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => $this->full_name,
            'employee_code'  => $this->employee_code,
            'department'     => $this->department ? [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            'balances'       => LeaveBalanceResource::collection($this->whenLoaded('leaveBalances')),
        ];
    }
}
