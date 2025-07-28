<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id','sku','price','stock'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            VariantAttributeValue::class,
            'product_variant_attribute_value',
            'variant_id','attribute_value_id'
        )->with('attribute');
    }

    public function values()
    {
        return $this->belongsToMany(
            VariantAttributeValue::class,
            'product_variant_values',
            'product_variant_id',
            'variant_attribute_value_id'
        )
        ->withPivot('id'); 
    }
}

