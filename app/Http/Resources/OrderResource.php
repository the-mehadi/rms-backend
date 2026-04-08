<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table' => $this->whenLoaded('table'),
            'user' => $this->whenLoaded('user'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status' => $this->status,
            'special_note' => $this->special_note,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
