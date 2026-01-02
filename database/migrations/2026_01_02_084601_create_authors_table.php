<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create authors table.
 *
 * Why separate table?
 * - Many-to-many relationship with manga (one author can have multiple manga)
 * - Enables author pages and search by author
 * - Slug for SEO-friendly URLs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Slug for SEO-friendly URLs (e.g., "eiichiro-oda")
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
