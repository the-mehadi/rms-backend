<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill_id' => $this->bill_id,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at?->toDateTimeString(),
        ];
    }
}

