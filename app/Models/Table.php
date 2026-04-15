<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'capacity',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
    public function activeOrder()
    {
        return $this->hasOne(Order::class)
            ->whereNotIn('status', ['served', 'cancelled'])
            ->whereDate('created_at', today())
            ->select('id', 'table_id', 'status', 'priority');
    }
}
