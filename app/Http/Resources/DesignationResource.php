<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,

            'title'          => $this->title,

            'code'           => $this->code,

            'description'    => $this->description,

            'status'         => $this->status,

            'department_id'  => $this->department_id,

            'department' => $this->department ? [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ] : null,

            'employee_count' => 0,

            'created_at'     => $this->created_at->toDateString(),
        ];
    }
}