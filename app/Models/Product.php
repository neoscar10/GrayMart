<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'price',
        'category_id',
        'images',
        'video_url',
        'is_reserved',
        'is_signed', 
        'min_increment',
        'reserve_price',
        'buy_now_price',
        'status',
        'is_active',
        'rejection_reason',
        'rejected_at',
    ];

    protected $casts = [
        'images'       => 'array',
        'is_reserved'  => 'boolean',
        'is_signed'   => 'boolean',
        'is_active'    => 'boolean',
        'rejected_at' => 'datetime',
    ];

    // auto‑slug from name
    protected static function booted()
    {
        static::saving(function (Product $p) {
            if (empty($p->slug)) {
                $p->slug = Str::slug($p->name);
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // aspa Auction related
    public function auctions()
    {
        return $this->hasMany(Auction::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function reviews()
    {
    return $this->morphMany(Review::class,'rateable');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

}
