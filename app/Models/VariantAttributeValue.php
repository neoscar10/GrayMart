<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantAttributeValue extends Model
{
    protected $fillable = ['attribute_id','value'];

    public function attribute()
    {
        return $this->belongsTo(VariantAttribute::class, 'attribute_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_attribute_value',
            'attribute_value_id','variant_id'
        );
    }
}
