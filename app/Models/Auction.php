<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    protected $fillable = [
        'product_id',
        'vendor_id',
        'starts_at',
        'ends_at',
        'anti_sniping_window',
        'anonymize_bidders',
        'status',
    ];

    protected $casts = [
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'anti_sniping_window'  => 'integer',
        'anonymize_bidders'    => 'boolean',
    ];

    // The product being auctioned
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // The vendor who created this auction
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // All bids placed on this auction
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    // Shortcut to the highest bid
    public function highestBid()
    {
        return $this->bids()->orderByDesc('amount')->first();
    }
}
