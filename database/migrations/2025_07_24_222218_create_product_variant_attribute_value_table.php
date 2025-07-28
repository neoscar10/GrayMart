<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variant_attribute_value', function (Blueprint $table) {
            $table->foreignId('variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();
            $table->foreignId('attribute_value_id')
                  ->constrained('variant_attribute_values')
                  ->cascadeOnDelete();

            $table->primary(['variant_id','attribute_value_id'], 
                            'pvav_variant_value_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_value');
    }
};
