<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantAttribute extends Model
{
    protected $fillable = ['name'];

    public function values()
    {
        return $this->hasMany(VariantAttributeValue::class, 'attribute_id');
    }
}
