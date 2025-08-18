<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        // 'vendor_id',
        'total_amount',
        'status',
        'shipping_address',
        'admin_note'
    ];

    protected $casts = [
        'shipping_address' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function vendor()
    // {
    //     return $this->belongsTo(User::class, 'vendor_id');
    // }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(User::class, 'order_items', 'order_id', 'vendor_id')->distinct();
    }
}
