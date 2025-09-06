<?php

// database/migrations/2025_01_01_000001_update_orders_and_items_for_multivendor.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // -------- orders ----------
        Schema::table('orders', function (Blueprint $table) {
            // vendor_id (per-vendor orders): add only if missing
            if (!Schema::hasColumn('orders', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            // payment & amounts (decimal with defaults; no unsignedDecimal)
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 40)->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 40)->default('unpaid')->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->default('USD')->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('currency');
            }
            if (!Schema::hasColumn('orders', 'shipping_amount')) {
                $table->decimal('shipping_amount', 12, 2)->default(0)->after('subtotal_amount');
            }
            if (!Schema::hasColumn('orders', 'discount_total')) {
                $table->decimal('discount_total', 12, 2)->default(0)->after('shipping_amount');
            }

            // external payment references (PayPal etc.)
            if (!Schema::hasColumn('orders', 'external_payment_id')) {
                $table->string('external_payment_id')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'external_payment_payload')) {
                $table->json('external_payment_payload')->nullable()->after('external_payment_id');
            }
        });

        // -------- order_items ----------
        Schema::table('order_items', function (Blueprint $table) {
            // variant support + snapshots
            if (!Schema::hasColumn('order_items', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('order_items', 'image')) {
                $table->string('image')->nullable()->after('product_name'); // relative path
            }
            if (!Schema::hasColumn('order_items', 'meta')) {
                $table->json('meta')->nullable()->after('image'); // e.g. {"variant_label":"Size:M | Color:Red"}
            }

            // ensure numeric columns exist
            if (!Schema::hasColumn('order_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('order_items', 'total_price')) {
                $table->decimal('total_price', 12, 2)->default(0);
            }

            // helpful index for vendor dashboards (named to avoid collisions)
            $table->index(['vendor_id', 'order_id'], 'order_items_vendor_order_idx');
        });
    }

    public function down(): void
    {
        // -------- order_items ----------
        Schema::table('order_items', function (Blueprint $table) {
            // drop index if present (safe to attempt once)
            $table->dropIndex('order_items_vendor_order_idx');

            // drop new columns if they exist
            if (Schema::hasColumn('order_items', 'variant_id')) {
                $table->dropColumn('variant_id');
            }
            if (Schema::hasColumn('order_items', 'product_name')) {
                $table->dropColumn('product_name');
            }
            if (Schema::hasColumn('order_items', 'image')) {
                $table->dropColumn('image');
            }
            if (Schema::hasColumn('order_items', 'meta')) {
                $table->dropColumn('meta');
            }
            // leave unit_price / total_price since they may be part of your existing schema
        });

        // -------- orders ----------
        Schema::table('orders', function (Blueprint $table) {
            // drop FK + column for vendor_id if present
            if (Schema::hasColumn('orders', 'vendor_id')) {
                // If it was created with constrained(), this helper will drop FK + column:
                $table->dropConstrainedForeignId('vendor_id');
            }

            // drop the other columns if present
            foreach ([
                'payment_method','payment_status','currency',
                'subtotal_amount','shipping_amount','discount_total',
                'external_payment_id','external_payment_payload',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
