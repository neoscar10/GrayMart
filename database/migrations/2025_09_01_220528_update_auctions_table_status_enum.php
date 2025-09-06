<?php

// database/migrations/2025_09_01_000000_update_auction_status_enum.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('auctions', function (Blueprint $table) {
            // If using MySQL ENUM:
            $table->enum('status', ['scheduled', 'live', 'ended', 'closed'])->default('scheduled')->change();

            // If you prefer varchar + check, comment the line above and use:
            // $table->string('status', 20)->default('scheduled')->change();
            // DB::statement("ALTER TABLE auctions ADD CONSTRAINT chk_auctions_status CHECK (status IN ('scheduled','live','ended'))");
        });
    }
    public function down(): void {
        // revert as needed
    }
};
