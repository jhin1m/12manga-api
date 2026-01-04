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

describe('List Authors', function () {
    it('can list all authors', function () {
        $response = $this->getJson('/api/v1/authors');

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
        expect(count($data))->toBe(2);
        expect($data[0])->toHaveKeys(['id', 'name', 'slug']);
    });
});

describe('Show Author', function () {
    it('shows author with paginated manga', function () {
        $author = Author::where('slug', 'eiichiro-oda')->first();

        // Create manga by this author
        $manga1 = MangaSeries::create([
            'title' => 'One Piece',
            'status' => 'ongoing',
        ]);
        $manga1->authors()->attach($author->id);

        $manga2 = MangaSeries::create([
            'title' => 'Wanted!',
            'status' => 'completed',
        ]);
        $manga2->authors()->attach($author->id);

        $response = $this->getJson('/api/v1/authors/eiichiro-oda');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'author' => ['id', 'name', 'slug'],
                    'manga',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'author' => [
                        'slug' => 'eiichiro-oda',
                        'name' => 'Eiichiro Oda',
                    ],
                ],
            ]);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(2);
    });

    it('returns 404 for non-existent author', function () {
        $response = $this->getJson('/api/v1/authors/non-existent-author');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Author not found',
            ]);
    });

    it('returns empty manga list for author with no manga', function () {
        $response = $this->getJson('/api/v1/authors/masashi-kishimoto');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'author' => [
                        'slug' => 'masashi-kishimoto',
                        'name' => 'Masashi Kishimoto',
                    ],
                ],
            ]);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(0);
    });
});

describe('Author Pagination', function () {
    it('paginates manga for author with many manga', function () {
        $author = Author::where('slug', 'eiichiro-oda')->first();

        // Create 20 manga by this author
        for ($i = 1; $i <= 20; $i++) {
            $manga = MangaSeries::create([
                'title' => "Manga {$i}",
                'status' => 'ongoing',
            ]);
            $manga->authors()->attach($author->id);
        }

        $response = $this->getJson('/api/v1/authors/eiichiro-oda');

        $response->assertStatus(200);

        $mangaData = $response->json('data.manga');
        expect($mangaData)->toBeArray();
        expect(count($mangaData))->toBe(15); // Default pagination limit
    });
});
