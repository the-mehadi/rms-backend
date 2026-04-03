<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemImage extends Model
{
    protected $fillable = [
        'menu_item_id',
        'original_filename',
        'url',
        'format',
        'hash',
        'alt_text',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
