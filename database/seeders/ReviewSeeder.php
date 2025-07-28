<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Faker\Factory as Faker;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // safely truncate the reviews table
        Schema::disableForeignKeyConstraints();
        Review::truncate();
        Schema::enableForeignKeyConstraints();

        $faker       = Faker::create();
        $customerIds = User::where('role', 'customer')->pluck('id')->all();
        $productIds  = Product::pluck('id')->all();
        $statuses    = ['pending', 'approved', 'rejected'];

        foreach (range(1, 20) as $_) {
            Review::create([
                'user_id'         => $faker->randomElement($customerIds),
                'rateable_id'     => $faker->randomElement($productIds),
                'rateable_type'   => Product::class,
                'rating'          => $faker->numberBetween(1, 5),
                'comment'         => $faker->sentence(),
                'reported'        => $faker->boolean(10),           // 10% chance reported
                'status'          => $faker->randomElement($statuses),
                'visible'         => true,
                'rejection_reason'=> $faker->optional()->sentence(),
                'created_at'      => now()->subDays($faker->numberBetween(0, 30)),
                'updated_at'      => now(),
            ]);
        }
    }
}
