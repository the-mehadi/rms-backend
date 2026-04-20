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
            'table_id' => $this->table_id,
            'table' => $this->whenLoaded('table'),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'user_id' => $this->user_id,
            'cashier' => $this->whenLoaded('cashier'),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'vat' => (float) $this->vat,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
