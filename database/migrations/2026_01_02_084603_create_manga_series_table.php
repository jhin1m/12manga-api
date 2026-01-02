<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create manga_series table - the core entity.
 *
 * Key design decisions:
 * - alt_titles: JSON for flexible multi-language titles
 *   Structure: {"en": "One Piece", "ja": "ワンピース", "vi": ["Đảo Hải Tặc", "Vua Hải Tặc"]}
 * - status: Enum for data integrity (ongoing/completed/hiatus)
 * - views_count: Denormalized counter for performance (avoid COUNT queries)
 * - average_rating: Pre-calculated for performance
 * - FULLTEXT index on (title, description) only - NOT on JSON field
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manga_series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Alternative titles in multiple languages
            // Structure: {"en": "...", "ja": "...", "vi": ["...", "..."]}
            $table->json('alt_titles')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Status enum for publication state
            $table->enum('status', ['ongoing', 'completed', 'hiatus'])->default('ongoing');
            $table->string('cover_image')->nullable();
            // Denormalized counters for performance
            $table->unsignedBigInteger('views_count')->default(0);
            // Pre-calculated rating (0.00 to 5.00)
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('status');
        });

        // FULLTEXT index for search - MySQL/MariaDB specific
        // Only on title and description, NOT on JSON alt_titles
        if (config('database.default') === 'mysql') {
            Schema::table('manga_series', function (Blueprint $table) {
                $table->fullText(['title', 'description']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manga_series');
    }
};
