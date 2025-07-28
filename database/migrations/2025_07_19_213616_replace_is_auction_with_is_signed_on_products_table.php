<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products','is_auction')) {
                $table->dropColumn('is_auction');
            }
            $table->boolean('is_signed')
                  ->default(false)
                  ->after('is_reserved')
                  ->comment('Vendor has provided a signed certificate?');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_signed');
            $table->boolean('is_auction')
                  ->default(false)
                  ->after('is_reserved');
        });
    }
};
