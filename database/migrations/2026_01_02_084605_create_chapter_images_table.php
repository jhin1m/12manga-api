<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create chapter_images table.
 *
 * Key design decisions:
 * - order: Integer for page ordering within chapter
 * - path: Storage path (not full URL) - URL generated via accessor
 * - Unique constraint on (chapter_id, order) prevents duplicate page numbers
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();
            // Page order (1, 2, 3, ...)
            $table->unsignedInteger('order');
            // Storage path (e.g., "manga/1/chapter-1/page-1.jpg")
            // URL is generated via model accessor
            $table->string('path');
            $table->timestamps();

            // Prevent duplicate page numbers per chapter
            $table->unique(['chapter_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_images');
    }
};
