<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'product_id',
        'file_path',
        'signed_by',
        'edition_number',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // The product this certificate is for
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
