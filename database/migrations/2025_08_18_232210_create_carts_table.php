<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Either user_id (auth carts) or session_id (guest carts)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable()->index();

            // Status lifecycle
            $table->enum('status', ['active','merged','abandoned','ordered'])
                  ->default('active')->index();

            // Currency & discounts (denormalized for quick reads)
            $table->string('currency', 3)->default('NGN');
            $table->string('coupon_code')->nullable()->index();
            $table->unsignedDecimal('discount_total', 12, 2)->default(0);

            // Optional denormalized totals (you can compute on the fly too)
            $table->unsignedDecimal('subtotal', 12, 2)->default(0);
            $table->unsignedDecimal('grand_total', 12, 2)->default(0);

            $table->timestamps();

            // You can also enforce a single active cart per user/session at the app layer
            // (partial unique indexes are DB-specific, so keep logic in the service)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
