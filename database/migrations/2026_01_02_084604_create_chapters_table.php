<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create chapters table.
 *
 * Key design decisions:
 * - number: DECIMAL(6,2) to support .5 chapters (e.g., 10.5, 100.5)
 * - uploader_id: Track who uploaded the chapter
 * - is_approved: Boolean for moderation workflow
 * - Unique constraint on (manga_series_id, number) prevents duplicate chapter numbers
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            // Foreign key to manga_series
            $table->foreignId('manga_series_id')
                ->constrained('manga_series')
                ->cascadeOnDelete();
            // Who uploaded this chapter
            $table->foreignId('uploader_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // Decimal for .5 chapters (e.g., 10, 10.5, 11)
            $table->decimal('number', 6, 2);
            $table->string('title')->nullable();
            $table->string('slug');
            // Moderation: chapters need approval before public display
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate chapter numbers per manga
            $table->unique(['manga_series_id', 'number']);
            // Common query patterns
            $table->index(['slug', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
