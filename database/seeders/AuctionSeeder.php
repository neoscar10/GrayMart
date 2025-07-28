<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Product;
use Carbon\Carbon;

class AuctionSeeder extends Seeder
{
    public function run()
    {
        // 1. Turn off FK checks so we can truncate safely
        Schema::disableForeignKeyConstraints();

        // 2. Wipe bids first, then auctions
        Bid::truncate();
        Auction::truncate();

        // 3. Re‑enable FK checks
        Schema::enableForeignKeyConstraints();

        // 4. Seed three sample auctions
        $samples = [
            ['product_id' => 1, 'starts' => now()->addDay(),    'ends' => now()->addDays(2),  'min' => 10,  'reserve' => 100, 'buy_now' => 300, 'status' => 'scheduled'],
            ['product_id' => 2, 'starts' => now()->subHour(),   'ends' => now()->addHour(),    'min' => 20,  'reserve' => 200, 'buy_now' => 600, 'status' => 'live'],
            ['product_id' => 3, 'starts' => now()->subDays(3),  'ends' => now()->subDay(),     'min' => 15,  'reserve' => 150, 'buy_now' => 450, 'status' => 'closed'],
        ];

        foreach ($samples as $s) {
            $product = Product::findOrFail($s['product_id']);

            Auction::create([
                'product_id'         => $product->id,
                'vendor_id'          => $product->vendor_id,
                'starts_at'          => $s['starts'],
                'ends_at'            => $s['ends'],
                'minimum_bid'        => $s['min'],
                'reserve_price'      => $s['reserve'],
                'buy_now_price'      => $s['buy_now'],
                'status'             => $s['status'],
                // anti_sniping_window & anonymize_bidders use their defaults
            ]);
        }
    }
}
