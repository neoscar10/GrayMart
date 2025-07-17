<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Top-level
        $electronics = Category::create(['name' => 'Electronics']);
        $fashion     = Category::create(['name' => 'Fashion']);
        $home        = Category::create(['name' => 'Home & Garden']);

        // Second level under Electronics
        $phones   = Category::create(['name' => 'Phones',   'parent_id' => $electronics->id]);
        $laptops  = Category::create(['name' => 'Laptops',  'parent_id' => $electronics->id]);
        $audio    = Category::create(['name' => 'Audio',    'parent_id' => $electronics->id]);

        // Third level under Phones
        Category::create(['name' => 'Smartphones', 'parent_id' => $phones->id]);
        Category::create(['name' => 'Feature Phones', 'parent_id' => $phones->id]);

        // Third level under Laptops
        Category::create(['name' => 'Ultrabooks',    'parent_id' => $laptops->id]);
        Category::create(['name' => 'Gaming Laptops','parent_id' => $laptops->id]);

        // Second level under Fashion
        $mens   = Category::create(['name' => 'Men',   'parent_id' => $fashion->id]);
        $womens = Category::create(['name' => 'Women', 'parent_id' => $fashion->id]);

        // Third level under Men
        Category::create(['name' => 'Shirts',   'parent_id' => $mens->id]);
        Category::create(['name' => 'Trousers', 'parent_id' => $mens->id]);

        // Third level under Women
        Category::create(['name' => 'Dresses', 'parent_id' => $womens->id]);
        Category::create(['name' => 'Skirts',  'parent_id' => $womens->id]);

        // Second level under Home & Garden
        $kitchen = Category::create(['name' => 'Kitchen',     'parent_id' => $home->id]);
        $furniture = Category::create(['name' => 'Furniture', 'parent_id' => $home->id]);

        // Third level under Furniture
        Category::create(['name' => 'Living Room', 'parent_id' => $furniture->id]);
        Category::create(['name' => 'Bedroom',     'parent_id' => $furniture->id]);
    }
}
