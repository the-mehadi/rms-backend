<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemImageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'original_filename' => $this->original_filename,
            'url' => $this->url,
            'format' => $this->format,
            'hash' => $this->hash,
            'alt_text' => $this->alt_text,
            'order' => $this->order,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
