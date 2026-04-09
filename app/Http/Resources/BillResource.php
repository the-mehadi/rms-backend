<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'vat' => $this->vat,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
        ];
    }
}

