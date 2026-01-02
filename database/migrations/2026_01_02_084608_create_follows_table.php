<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table: follows (user follows manga)
 *
 * Why pivot table?
 * - Many-to-many: One user can follow multiple manga
 * - One manga can be followed by multiple users
 * - Timestamps to track when user started following
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('manga_series_id')
                ->constrained('manga_series')
                ->cascadeOnDelete();
            // Track when user followed - useful for "followed at" display
            $table->timestamps();

            // Composite primary key prevents duplicate follows
            $table->primary(['user_id', 'manga_series_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
