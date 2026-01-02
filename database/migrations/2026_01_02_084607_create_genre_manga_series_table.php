<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table: genre_manga_series
 *
 * Why pivot table?
 * - Many-to-many: One manga can have multiple genres
 * - One genre contains many manga series
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genre_manga_series', function (Blueprint $table) {
            $table->foreignId('genre_id')
                ->constrained('genres')
                ->cascadeOnDelete();
            $table->foreignId('manga_series_id')
                ->constrained('manga_series')
                ->cascadeOnDelete();

            // Composite primary key prevents duplicates
            $table->primary(['genre_id', 'manga_series_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_manga_series');
    }
};
