<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'vendor_id',
        'variant_id',     // NEW
        'product_name',   // NEW (snapshot)
        'image',          // NEW (snapshot)
        'meta',           // NEW (json: variant_label, etc.)
    ];

    protected $casts = [
        'meta' => 'array',
        'unit_price' => 'decimal:2',
        'total_price'=> 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
