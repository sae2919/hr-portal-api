<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'code'         => $this->code,
            'days_per_year'=> $this->days_per_year,
            'carry_forward'=> $this->carry_forward,
            'is_paid'      => $this->is_paid,
            'color'        => $this->color,
            'description'  => $this->description,
            'status'       => $this->status,
            'created_at'   => $this->created_at->toDateString(),
        ];
    }
}