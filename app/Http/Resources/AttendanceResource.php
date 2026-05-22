<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'employee_id'    => $this->employee_id,
            'employee'       => $this->whenLoaded('employee', fn() => [
                'id'            => $this->employee->id,
                'full_name'     => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
                'department'    => $this->employee->department?->name,
            ]),
            'date'           => $this->date->toDateString(),
            'check_in'       => $this->check_in,
            'check_out'      => $this->check_out,
            'worked_hours'   => $this->worked_hours,
            'status'         => $this->status,
            'overtime_hours' => $this->overtime_hours,
            'note'           => $this->note,
            'created_at'     => $this->created_at->toDateString(),
        ];
    }
}