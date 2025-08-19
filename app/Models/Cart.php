<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id','session_id','status','currency','coupon_code',
        'discount_total','subtotal','grand_total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Convenience computed totals if you don't want denormalized columns:
    public function getComputedSubtotalAttribute(): float
    {
        return (float) $this->items->sum('total_amount');
    }

    public function getComputedGrandTotalAttribute(): float
    {
        return max(0, $this->computed_subtotal - (float) $this->discount_total);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}
