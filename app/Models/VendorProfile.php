<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorProfile extends Model
{
    protected $fillable = [
        'user_id','store_name','slug',
        'logo_path','banner_path',
        'email','phone','whatsapp','website',
        'socials','description',
        'address_line','city','state','country','postal_code',
        'place_id','lat','lng','opening_hours',
        'published',
    ];

    protected $casts = [
        'socials'       => 'array',
        'opening_hours' => 'array',
        'published'     => 'boolean',
        'lat'           => 'float',
        'lng'           => 'float',
    ];

    protected static function booted()
    {
        static::saving(function (VendorProfile $p) {
            if (empty($p->slug) && !empty($p->store_name)) {
                $p->slug = static::uniqueSlug($p->store_name, $p->id);
            }
            if (!empty($p->store_name) && $p->isDirty('store_name')) {
                $p->slug = static::uniqueSlug($p->store_name, $p->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)
            ->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path ? asset('storage/'.$this->banner_path) : null;
    }
}
