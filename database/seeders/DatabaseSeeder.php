<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main database seeder - orchestrates all seeders.
 *
 * Order matters!
 * 1. RolesAndPermissions - Must run before users (for role assignment)
 * 2. Genre - Must run before manga (manga needs genres)
 * 3. Manga - Creates everything else (users, authors, chapters, images)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Order is important: dependencies must be seeded first
        $this->call([
            // 1. Roles and permissions (needed for user role assignment)
            RolesAndPermissionsSeeder::class,

            // 2. Genres (needed for manga-genre relationships)
            GenreSeeder::class,

            // 3. Manga data (creates admin user, authors, chapters, images)
            MangaSeeder::class,
        ]);
    }
}
