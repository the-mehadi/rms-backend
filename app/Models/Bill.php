<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'user_id',
        'subtotal',
        'discount',
        'vat',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'vat' => 'float',
        'total_amount' => 'float',
        'status' => 'string',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Multiple orders for this bill (through pivot table)
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'bill_orders', 'bill_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
