<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table: author_manga_series
 *
 * Why pivot table?
 * - Many-to-many: One manga can have multiple authors (writer + illustrator)
 * - One author can work on multiple manga series
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_manga_series', function (Blueprint $table) {
            $table->foreignId('author_id')
                ->constrained('authors')
                ->cascadeOnDelete();
            $table->foreignId('manga_series_id')
                ->constrained('manga_series')
                ->cascadeOnDelete();

            // Composite primary key prevents duplicates
            $table->primary(['author_id', 'manga_series_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_manga_series');
    }
};
