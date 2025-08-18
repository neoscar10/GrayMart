<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('store_name')->index();
            $table->string('slug')->unique();

            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();

            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();

            // Social links (json: {facebook, instagram, twitter, tiktok, youtube})
            $table->json('socials')->nullable();

            // About
            $table->text('description')->nullable();

            // Location
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('place_id')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Opening hours, etc.
            $table->json('opening_hours')->nullable();

            // Visibility
            $table->boolean('published')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
