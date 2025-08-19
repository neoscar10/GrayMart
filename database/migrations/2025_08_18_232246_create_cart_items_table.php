<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();

            // Product + optional variant
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()
                  ->constrained('product_variants')->nullOnDelete();

            // Helpful denorms for vendor splitting at checkout
            $table->foreignId('vendor_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            // Snapshot fields for display consistency if product changes later
            $table->string('title_snapshot')->nullable();
            $table->string('image_snapshot')->nullable(); // path under storage/
            $table->boolean('is_limited')->default(false); // from products.is_reserved

            // Money + qty
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedDecimal('unit_amount', 12, 2);
            $table->unsignedDecimal('total_amount', 12, 2);

            $table->timestamps();

            // Keep 1 row per (cart, product, variant)
            $table->unique(['cart_id','product_id','variant_id']);
            $table->index(['vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
