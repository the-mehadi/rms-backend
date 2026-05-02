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

    /**
     * Get an unpaid bill for a table, if it exists.
     */
    public static function getUnpaidBillForTable(int $tableId): ?self
    {
        return self::query()
            ->where('table_id', $tableId)
            ->where('status', 'unpaid')
            ->first();
    }

    /**
     * Recalculate bill totals based on linked orders.
     * Updates subtotal and VAT, then recalculates total_amount.
     */
    public function recalculateTotals(): self
    {
        $newSubtotal = 0.0;

        // Sum all order item prices
        foreach ($this->orders as $order) {
            foreach ($order->items as $item) {
                $newSubtotal += (float) $item->price * (int) $item->quantity;
            }
        }

        // Preserve the bill's discount percentage (stored in vat column as the percentage rate)
        // Get the VAT percentage rate from bill (stored when bill was created)
        // We'll need to extract this from the current VAT amount
        $vatPercentage = 5.0; // Default
        if ($this->subtotal > 0 && $this->discount < $this->subtotal) {
            $denominator = $this->subtotal - $this->discount;
            if ($denominator > 0) {
                $vatPercentage = ($this->vat / $denominator) * 100;
            }
        }

        // Recalculate VAT as percentage of (subtotal - discount)
        $newVatAmount = ($newSubtotal - $this->discount) * ($vatPercentage / 100);
        $newTotalAmount = ($newSubtotal - $this->discount) + $newVatAmount;

        $this->update([
            'subtotal' => round($newSubtotal, 2),
            'vat' => round($newVatAmount, 2),
            'total_amount' => round($newTotalAmount, 2),
        ]);

        return $this->fresh();
    }
}
