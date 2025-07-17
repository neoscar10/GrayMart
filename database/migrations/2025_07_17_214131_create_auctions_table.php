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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')
                  ->constrained('users')->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->integer('anti_sniping_window')
                  ->default(30)
                  ->comment('seconds before end_at to auto‑extend');
            $table->boolean('anonymize_bidders')
                  ->default(false);
            $table->enum('status', ['scheduled','live','closed'])
                  ->default('scheduled');
            $table->timestamps();
    
            $table->index(['status','starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
