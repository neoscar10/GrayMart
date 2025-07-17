<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ensure at least one vendor exists
        $vendor = User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name'     => 'Demo Vendor',
                'password' => bcrypt('password'),
                'role'     => 'vendor',
            ]
        );

        $categories = Category::pluck('id')->all();
        $imagesPool = [
            'https://via.placeholder.com/300x200?text=1',
            'https://via.placeholder.com/300x200?text=2',
            'https://via.placeholder.com/300x200?text=3',
        ];

        // generate 20 demo products
        for ($i = 1; $i <= 20; $i++) {
            Product::create([
                'vendor_id'   => $vendor->id,
                'name'        => "Demo Product {$i}",
                'slug'        => Str::slug("Demo Product {$i}"),
                'description' => "This is a sample description for product {$i}.",
                'price'       => rand(1000, 50000) / 100,
                'category_id' => Arr::random($categories),
                'images'      => Arr::random($imagesPool, rand(1, 3)),
                'video_url'   => rand(0,1) ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
                'is_reserved' => (bool) rand(0,1),
                'status'      => Arr::random(['pending','approved','rejected']),
                'is_active'   => 1,
            ]);
        }
    }
}
