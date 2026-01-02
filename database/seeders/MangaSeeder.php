<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Chapter;
use App\Models\ChapterImage;
use App\Models\Genre;
use App\Models\MangaSeries;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds sample manga data for development/testing.
 *
 * Creates:
 * - 1 admin user
 * - 5 sample manga with real-world-like data
 * - Authors for each manga
 * - 3-5 chapters per manga (some with .5 chapters)
 * - 5 images per chapter
 */
class MangaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user as uploader for all sample chapters
        $admin = $this->createAdminUser();

        // Get genre models for later assignment
        $genres = Genre::all()->keyBy('name');

        // Create authors and manga
        $mangaData = $this->getMangaData();

        foreach ($mangaData as $data) {
            $this->createMangaWithRelations($data, $admin, $genres);
        }
    }

    /**
     * Create admin user with proper role.
     */
    private function createAdminUser(): User
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@manga.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign admin role (from RolesAndPermissionsSeeder)
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * Sample manga data with real-world-like information.
     *
     * Why this structure?
     * - Demonstrates alt_titles JSON format with multiple languages
     * - Shows variety of statuses
     * - Realistic chapter counts and .5 chapters
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMangaData(): array
    {
        return [
            [
                'title' => 'One Piece',
                'alt_titles' => [
                    'en' => 'One Piece',
                    'ja' => 'ワンピース',
                    'ja_ro' => 'Wan Pīsu',
                    'vi' => ['Đảo Hải Tặc', 'Vua Hải Tặc'],
                ],
                'description' => 'Monkey D. Luffy sets off on an adventure to find the legendary treasure One Piece and become the King of the Pirates.',
                'status' => 'ongoing',
                'authors' => ['Eiichiro Oda'],
                'genres' => ['Action', 'Adventure', 'Comedy', 'Shounen'],
                'chapters' => [1, 2, 3, 4, 5],
            ],
            [
                'title' => 'Naruto',
                'alt_titles' => [
                    'en' => 'Naruto',
                    'ja' => 'ナルト',
                    'vi' => ['Naruto'],
                ],
                'description' => 'Naruto Uzumaki, a young ninja with a demon fox sealed inside him, dreams of becoming Hokage.',
                'status' => 'completed',
                'authors' => ['Masashi Kishimoto'],
                'genres' => ['Action', 'Adventure', 'Shounen'],
                'chapters' => [1, 2, 3],
            ],
            [
                'title' => 'Attack on Titan',
                'alt_titles' => [
                    'en' => 'Attack on Titan',
                    'ja' => '進撃の巨人',
                    'ja_ro' => 'Shingeki no Kyojin',
                    'vi' => ['Đại Chiến Người Khổng Lồ', 'Tiến Kích Người Khổng Lồ'],
                ],
                'description' => 'In a world where humanity lives inside cities surrounded by walls due to Titans, Eren Yeager vows to destroy them all.',
                'status' => 'completed',
                'authors' => ['Hajime Isayama'],
                'genres' => ['Action', 'Drama', 'Fantasy', 'Horror'],
                'chapters' => [1, 2, 2.5, 3, 4], // Includes a .5 chapter
            ],
            [
                'title' => 'My Hero Academia',
                'alt_titles' => [
                    'en' => 'My Hero Academia',
                    'ja' => '僕のヒーローアカデミア',
                    'ja_ro' => 'Boku no Hero Academia',
                    'vi' => ['Học Viện Siêu Anh Hùng'],
                ],
                'description' => 'In a world where most people have superpowers, Izuku Midoriya is born without one but still dreams of becoming a hero.',
                'status' => 'ongoing',
                'authors' => ['Kohei Horikoshi'],
                'genres' => ['Action', 'Comedy', 'Shounen', 'Supernatural'],
                'chapters' => [1, 2, 3, 4],
            ],
            [
                'title' => 'Solo Leveling',
                'alt_titles' => [
                    'en' => 'Solo Leveling',
                    'ko' => '나 혼자만 레벨업',
                    'ja' => '俺だけレベルアップな件',
                    'vi' => ['Tôi Thăng Cấp Một Mình'],
                ],
                'description' => 'Sung Jin-Woo, the weakest hunter, gains the power to level up alone after a mysterious incident.',
                'status' => 'completed',
                'authors' => ['Chugong', 'Dubu'],
                'genres' => ['Action', 'Adventure', 'Fantasy', 'Seinen'],
                'chapters' => [1, 1.5, 2, 3], // Includes a .5 chapter
            ],
        ];
    }

    /**
     * Create a manga series with all its relations.
     *
     * @param array<string, mixed> $data
     * @param \Illuminate\Database\Eloquent\Collection<int, Genre> $genres
     */
    private function createMangaWithRelations(
        array $data,
        User $uploader,
        $genres
    ): void {
        // Create manga series
        $manga = MangaSeries::create([
            'title' => $data['title'],
            'alt_titles' => $data['alt_titles'],
            'description' => $data['description'],
            'status' => $data['status'],
            'views_count' => rand(1000, 100000),
            'average_rating' => rand(350, 500) / 100, // 3.50 to 5.00
        ]);

        // Create and attach authors
        foreach ($data['authors'] as $authorName) {
            $author = Author::firstOrCreate(['name' => $authorName]);
            $manga->authors()->attach($author->id);
        }

        // Attach genres (already exist from GenreSeeder)
        $genreIds = [];
        foreach ($data['genres'] as $genreName) {
            if ($genres->has($genreName)) {
                $genreIds[] = $genres->get($genreName)->id;
            }
        }
        $manga->genres()->attach($genreIds);

        // Create chapters
        foreach ($data['chapters'] as $chapterNum) {
            $this->createChapterWithImages($manga, $uploader, $chapterNum);
        }
    }

    /**
     * Create a chapter with sample images.
     */
    private function createChapterWithImages(
        MangaSeries $manga,
        User $uploader,
        float $chapterNumber
    ): void {
        // Create chapter (slug auto-generated)
        $chapter = Chapter::create([
            'manga_series_id' => $manga->id,
            'uploader_id' => $uploader->id,
            'number' => $chapterNumber,
            'title' => 'Chapter ' . $chapterNumber,
            'is_approved' => true, // Sample data is pre-approved
        ]);

        // Create 5 sample images per chapter
        // Path follows pattern: manga/{manga_id}/chapter-{number}/page-{order}.jpg
        for ($page = 1; $page <= 5; $page++) {
            ChapterImage::create([
                'chapter_id' => $chapter->id,
                'order' => $page,
                'path' => sprintf(
                    'manga/%d/chapter-%s/page-%d.jpg',
                    $manga->id,
                    str_replace('.', '-', (string) $chapterNumber), // 2.5 -> 2-5
                    $page
                ),
            ]);
        }
    }
}
