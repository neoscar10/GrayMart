<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_auction')->default(false)->after('is_reserved');
        $table->decimal('min_increment', 10, 2)
              ->nullable()
              ->after('is_auction');
        $table->decimal('reserve_price', 10, 2)
              ->nullable()
              ->after('min_increment');
        $table->decimal('buy_now_price', 10, 2)
              ->nullable()
              ->after('reserve_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
