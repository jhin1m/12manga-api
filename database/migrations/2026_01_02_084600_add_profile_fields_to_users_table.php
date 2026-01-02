<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add profile fields to users table for manga reader.
 *
 * Why these fields?
 * - avatar: User profile picture URL
 * - bio: Short user description/biography
 * - profile_slug: SEO-friendly URL identifier (via Spatie Sluggable)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Avatar URL - nullable because users may not upload one
            $table->string('avatar')->nullable()->after('email');

            // Biography text - nullable, limited length for performance
            $table->text('bio')->nullable()->after('avatar');

            // URL-friendly profile identifier (e.g., "john-doe")
            // Unique to prevent duplicates
            $table->string('profile_slug')->unique()->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'bio', 'profile_slug']);
        });
    }
};
