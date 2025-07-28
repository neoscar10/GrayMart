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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');                   // reviewer
            $table->morphs('rateable');                   // product/vendor
            $table->tinyInteger('rating')->unsigned();    // 1–5 stars
            $table->text('comment')->nullable();
            $table->boolean('reported')->default(false);
            $table->enum('status',['pending','approved','rejected'])
                ->default('pending');
            $table->boolean('visible')->default(true);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['status','reported']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
