<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id','product_id','variant_id','vendor_id',
        'title_snapshot','image_snapshot','is_limited',
        'quantity','unit_amount','total_amount',
    ];

    protected $casts = [
        'is_limited' => 'boolean',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
