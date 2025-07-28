<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // customer
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            // vendor (for multi‑vendor)
            $table->foreignId('vendor_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', [
                'pending','processing','shipped',
                'delivered','cancelled'
            ])->default('pending');
            // embed shipping address as JSON for flexibility
            $table->json('shipping_address');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
