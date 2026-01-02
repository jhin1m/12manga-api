<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create genres table.
 *
 * Why separate table?
 * - Many-to-many relationship with manga
 * - Enables filtering manga by genre
 * - Slug for SEO-friendly URLs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Slug for SEO-friendly URLs (e.g., "slice-of-life")
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
