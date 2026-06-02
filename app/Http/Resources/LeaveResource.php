<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'employee_id'                => $this->employee_id,
            'employee'                   => $this->whenLoaded('employee', fn() => [
                'id'            => $this->employee->id,
                'full_name'     => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
                'department'    => $this->employee->department?->name,
            ]),
            'leave_type_id'              => $this->leave_type_id,
            'leave_type'                 => $this->whenLoaded('leaveType', fn() => [
                'id'    => $this->leaveType->id,
                'name'  => $this->leaveType->name,
                'color' => $this->leaveType->color,
            ]),
            'start_date'                 => $this->start_date->toDateString(),
            'end_date'                   => $this->end_date->toDateString(),
            'days'                       => $this->days,
            'reason'                     => $this->reason,
            'status'                     => $this->status,
            'rejection_reason'           => $this->rejection_reason,
            'approved_by'                => $this->approved_by,
            'approved_at'                => $this->approved_at?->toDateTimeString(),
            'team_lead_status'           => $this->team_lead_status,
            'team_lead_id'               => $this->team_lead_id,
            'team_lead'                  => $this->whenLoaded('teamLead', fn() => [
                'id'   => $this->teamLead->id,
                'name' => $this->teamLead->name,
            ]),
            'team_lead_rejection_reason' => $this->team_lead_rejection_reason,
            'team_lead_acted_at'         => $this->team_lead_acted_at?->toDateTimeString(),
            'hr_override'                => $this->hr_override,
            'created_at'                 => $this->created_at->toDateString(),
        ];
    }
}