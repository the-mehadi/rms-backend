<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_number' => $this->table_number,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'active_order' => $this->whenLoaded('activeOrder'),
        ];
    }
}
