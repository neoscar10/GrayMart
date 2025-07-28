<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->decimal('minimum_bid', 10, 2)
                  ->default(0.00)
                  ->after('ends_at');
            $table->decimal('reserve_price', 10, 2)
                  ->default(0.00)
                  ->after('minimum_bid');
            $table->decimal('buy_now_price', 10, 2)
                  ->nullable()
                  ->after('reserve_price');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['minimum_bid', 'reserve_price', 'buy_now_price']);
        });
    }
};
