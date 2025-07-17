<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // The auction this bid belongs to
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    // The user who placed this bid
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
