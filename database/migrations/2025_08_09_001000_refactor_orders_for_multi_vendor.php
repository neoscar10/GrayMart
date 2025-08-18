<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        // 1) Add vendor_id to order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('product_id')
                  ->constrained('users')->nullOnDelete();
            $table->index(['vendor_id']);
        });

        // 2) Backfill vendor_id on order_items from products.vendor_id or orders.vendor_id
        // Prefer product.vendor_id (canonical owner) and fallback to orders.vendor_id if present.
        DB::statement("
            UPDATE order_items oi
            JOIN products p ON p.id = oi.product_id
            SET oi.vendor_id = p.vendor_id
            WHERE oi.vendor_id IS NULL
        ");

        // Optional fallback if you had data in orders.vendor_id and some products lacked vendor_id:
        if (Schema::hasColumn('orders', 'vendor_id')) {
            DB::statement("
                UPDATE order_items oi
                JOIN orders o ON o.id = oi.order_id
                SET oi.vendor_id = o.vendor_id
                WHERE oi.vendor_id IS NULL
            ");
        }

        // 3) Drop vendor_id from orders
        if (Schema::hasColumn('orders', 'vendor_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vendor_id');
            });
        }

        // (Optional) Add a covering index for vendor dashboards
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['vendor_id', 'created_at']);
            $table->index(['vendor_id', 'order_id']);
        });
    }

    public function down(): void
    {
        // restore vendor_id on orders (nullable)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('user_id')
                  ->constrained('users')->nullOnDelete();
        });

        // best‑effort rehydrate orders.vendor_id from first item’s vendor
        DB::statement("
            UPDATE orders o
            JOIN (
                SELECT order_id, MIN(vendor_id) AS vendor_id
                FROM order_items
                GROUP BY order_id
            ) x ON x.order_id = o.id
            SET o.vendor_id = x.vendor_id
        ");

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
