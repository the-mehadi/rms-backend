<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'payment_method',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'payment_method' => 'string',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // Get all orders related to this payment through the bill (requires bill to be loaded)
    public function orders()
    {
        if ($this->relationLoaded('bill') && $this->bill) {
            return $this->bill->orders();
        }
        // Fallback: load bill and orders
        return $this->load('bill')->bill->orders();
    }
}
