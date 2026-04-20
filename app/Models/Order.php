<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'user_id',
        'status',
        'special_note',
    ];

    protected $casts = [
        'status' => 'string',
        'special_note' => 'string',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Multiple bills can contain this order (through pivot table)
    public function bills(): BelongsToMany
    {
        return $this->belongsToMany(Bill::class, 'bill_orders', 'order_id', 'bill_id');
    }

    // Legacy: Keep for backward compatibility if needed
    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }
}
