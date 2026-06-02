<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'code'           => $this->code,
            'description'    => $this->description,
            'status'         => $this->status,
            'parent_id'      => $this->parent_id,
            'parent'         => $this->whenLoaded('parent', fn() => [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
            ]),
            'employee_count'  => $this->employees_count,
            'employees_count' => $this->employees_count,
            'created_at'     => $this->created_at->toDateString(),
        ];
    }
}