<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Bid;
use App\Models\Auction;
use App\Models\User;

class BidSeeder extends Seeder
{
    public function run()
    {
        // Temporarily disable foreign‑key checks so we can truncate safely
        Schema::disableForeignKeyConstraints();
        Bid::truncate();
        Schema::enableForeignKeyConstraints();

        // Grab one live auction and the only customer in your system
        $auction  = Auction::where('status', 'live')->first();
        $customer = User::where('role', 'customer')->first();

        if ($auction && $customer) {
            Bid::create([
                'auction_id' => $auction->id,
                'user_id'    => $customer->id,
                'amount'     => max($auction->minimum_bid + 5, 1), // e.g. min bid + 5
            ]);
        }
    }
}
