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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('signed_by')->nullable();
            $table->string('edition_number')->nullable();
            $table->enum('status',['pending','approved','rejected'])
                ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
