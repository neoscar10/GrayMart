<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'is_active',
        'order_column',
        'image',
    ];

    // Auto-generate slug from name if not provided
    protected static function booted()
    {
        static::saving(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Parent category
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Child categories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function getFullNameAttribute(): string
    {
        $segments = [$this->name];
        $p = $this->parent;
        while ($p) {
            array_unshift($segments, $p->name);
            $p = $p->parent;
        }
        return implode(' ‑ ', $segments);
    }
}
