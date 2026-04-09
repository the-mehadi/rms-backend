<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bill' => BillResource::make($this->resource),
            'items' => OrderItemResource::collection($this->resource->order?->items ?? []),
            'payments' => PaymentResource::collection($this->resource->payments ?? []),
        ];
    }
}

