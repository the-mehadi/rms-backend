<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = [];
        $orders = $this->resource->orders ?? collect();

        // Collect all items from all orders in the bill
        foreach ($orders as $order) {
            $items = array_merge($items, $order->items?->toArray() ?? []);
        }

        return [
            'bill' => BillResource::make($this->resource),
            'orders' => OrderResource::collection($orders),
            'items' => OrderItemResource::collection($items),
            'payments' => PaymentResource::collection($this->resource->payments ?? []),
        ];
    }
}
