<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'vendor_id',           
        'subtotal_amount',      
        'shipping_amount',      
        'discount_total',       
        'total_amount',
        'currency',            
        'payment_method',       
        'payment_status',       
        'external_payment_id',  
        'external_payment_payload', 
        'status',
        'shipping_address',
        'admin_note',
    ];
    protected $casts = [
        'shipping_address' => 'array',
        'external_payment_payload' => 'array',
        'subtotal_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_total'  => 'decimal:2',
        'total_amount'    => 'decimal:2',
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
