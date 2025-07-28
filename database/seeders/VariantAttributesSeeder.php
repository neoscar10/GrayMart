<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue as var_attr;

class VariantAttributesSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Size attribute + values
        $size = VariantAttribute::create(['name' => 'Size']);
        foreach (['S','M','L','XL'] as $v) {
            var_attr::create([
                'attribute_id' => $size->id,
                'value'        => $v,
            ]);
        }

        // 2) Color attribute + values
        $color = VariantAttribute::create(['name' => 'Color']);
        foreach (['Red','Green','Blue'] as $v) {
            var_attr::create([
                'attribute_id' => $color->id,
                'value'        => $v,
            ]);
        }
    }
}
