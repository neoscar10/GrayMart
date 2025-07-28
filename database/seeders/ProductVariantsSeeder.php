<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;

class ProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        // Grab a handful of products—if none exist, nothing to do
        $products = Product::take(5)->get();
        if ($products->isEmpty()) {
            return;
        }

        // Preload values
        $sizes  = VariantAttributeValue::whereHas('attribute', fn($q) => $q->where('name','Size'))->get();
        $colors = VariantAttributeValue::whereHas('attribute', fn($q) => $q->where('name','Color'))->get();

        foreach ($products as $product) {
            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    // create one variant per size+color
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku'        => strtoupper($product->id . '-' . $size->value . '-' . $color->value),
                        // price override: +10% for demo
                        'price'      => round($product->price * 1.1, 2),
                        'stock'      => rand(0, 20),
                    ]);
                    // attach its two attribute‐values
                    $variant->attributeValues()->attach([
                        $size->id,
                        $color->id,
                    ]);
                }
            }
        }
    }
}
