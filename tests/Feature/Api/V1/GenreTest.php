<?php

declare(strict_types=1);

use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Genre;
use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed genres for tests
    Genre::insert([
        ['name' => 'Action', 'slug' => 'action', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Adventure', 'slug' => 'adventure', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Comedy', 'slug' => 'comedy', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Seed authors for tests
    Author::insert([
        ['name' => 'Eiichiro Oda', 'slug' => 'eiichiro-oda', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Masashi Kishimoto', 'slug' => 'masashi-kishimoto', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

describe('List Genres', function () {
    it('can list all genres', function () {
        $response = $this->getJson('/api/v1/genres');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        expect($data)->toBeArray();
        expect(count($data))->toBe(3);
        expect($data[0])->toHaveKeys(['id', 'name', 'slug']);
    });
});

describe('Show Genre', function () {
    it('shows genre with paginated manga', function () {
        $actionGenre = Genre::where('slug', 'action')->first();

        // Create manga with action genre
        $manga1 = MangaSeries::create([
            'title' => 'Action Manga 1',
            'status' => 'ongoing',
        ]);
        $manga1->genres()->attach($actionGenre->id);

        $manga2 = MangaSeries::create([
            'title' => 'Action Manga 2',
            'status' => 'ongoing',
        ]);
        $manga2->genres()->attach($actionGenre->id);

        $response = $this->getJson('/api/v1/genres/action');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'genre' => ['id', 'name', 'slug'],
                    'manga',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'genre' => [
                        'slug' => 'action',
                        'name' => 'Action',
                    ],
                ],
            ]);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(2);
    });

    it('returns 404 for non-existent genre', function () {
        $response = $this->getJson('/api/v1/genres/non-existent-genre');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Genre not found',
            ]);
    });

    it('returns empty manga list for genre with no manga', function () {
        $response = $this->getJson('/api/v1/genres/comedy');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'genre' => [
                        'slug' => 'comedy',
                        'name' => 'Comedy',
                    ],
                ],
            ]);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(0);
    });
});

describe('Genre Pagination', function () {
    it('paginates manga for genre with many manga', function () {
        $actionGenre = Genre::where('slug', 'action')->first();

        // Create 20 manga with action genre
        for ($i = 1; $i <= 20; $i++) {
            $manga = MangaSeries::create([
                'title' => "Action Manga {$i}",
                'status' => 'ongoing',
            ]);
            $manga->genres()->attach($actionGenre->id);
        }

        $response = $this->getJson('/api/v1/genres/action');

        $response->assertStatus(200);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(15); // Default pagination limit
    });
});
