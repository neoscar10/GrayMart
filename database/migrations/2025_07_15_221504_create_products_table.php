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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('vendor_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
           
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->onDelete('set null');
            
            $table->json('images')->nullable();
           
            $table->string('video_url')->nullable();
            
            $table->boolean('is_reserved')->default(false);
            // approval & status
            $table->enum('status', ['pending','approved','rejected'])
                  ->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
