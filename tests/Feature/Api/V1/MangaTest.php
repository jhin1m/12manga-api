<?php

declare(strict_types=1);

use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Genre;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles for permission system
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'user', 'guard_name' => 'web']);

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

describe('List Manga', function () {
    it('can list manga with pagination', function () {
        // Create sample manga
        MangaSeries::create([
            'title' => 'One Piece',
            'description' => 'A pirate adventure',
            'status' => 'ongoing',
        ]);

        MangaSeries::create([
            'title' => 'Naruto',
            'description' => 'Ninja story',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/manga');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Response data contains paginated manga resources
        expect($response->json('data'))->toBeArray();
        expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
    });

    it('can filter manga by status', function () {
        MangaSeries::create([
            'title' => 'One Piece',
            'status' => 'ongoing',
        ]);

        MangaSeries::create([
            'title' => 'Naruto',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/manga?status=ongoing');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['status'])->toBe('ongoing');
    });

    it('can filter manga by genre', function () {
        $actionGenre = Genre::where('slug', 'action')->first();
        $comedyGenre = Genre::where('slug', 'comedy')->first();

        $manga1 = MangaSeries::create(['title' => 'Action Manga', 'status' => 'ongoing']);
        $manga1->genres()->attach($actionGenre->id);

        $manga2 = MangaSeries::create(['title' => 'Comedy Manga', 'status' => 'ongoing']);
        $manga2->genres()->attach($comedyGenre->id);

        $response = $this->getJson('/api/v1/manga?genre=action');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(1);
    });
});

describe('Show Single Manga', function () {
    it('returns single manga by slug', function () {
        $manga = MangaSeries::create([
            'title' => 'One Piece',
            'description' => 'A pirate adventure',
            'status' => 'ongoing',
        ]);

        $response = $this->getJson("/api/v1/manga/{$manga->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'status',
                    'authors',
                    'genres',
                    'chapters_count',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'One Piece',
                    'slug' => $manga->slug,
                ],
            ]);
    });

    it('returns 404 for non-existent slug', function () {
        $response = $this->getJson('/api/v1/manga/non-existent-manga');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Manga not found',
            ]);
    });

    it('increments view count when showing manga', function () {
        $manga = MangaSeries::create([
            'title' => 'One Piece',
            'status' => 'ongoing',
        ]);

        $initialViews = $manga->views_count;

        $this->getJson("/api/v1/manga/{$manga->slug}");

        expect($manga->fresh()->views_count)->toBe($initialViews + 1);
    });
});

describe('Search Manga', function () {
    it('can search manga by keyword', function () {
        MangaSeries::create([
            'title' => 'One Piece',
            'description' => 'A pirate adventure on the high seas',
            'status' => 'ongoing',
        ]);

        MangaSeries::create([
            'title' => 'Naruto',
            'description' => 'A ninja story',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/manga/search?q=pirate');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBeGreaterThanOrEqual(1);
    });
});

describe('Popular Manga', function () {
    it('returns popular manga ordered by views', function () {
        MangaSeries::create([
            'title' => 'Low Views',
            'views_count' => 100,
            'status' => 'ongoing',
        ]);

        MangaSeries::create([
            'title' => 'High Views',
            'views_count' => 10000,
            'status' => 'ongoing',
        ]);

        $response = $this->getJson('/api/v1/manga/popular?limit=2');

        $response->assertStatus(200);
        expect($response->json('data.0.title'))->toBe('High Views');
    });
});

describe('Latest Manga', function () {
    it('returns latest updated manga', function () {
        $oldManga = MangaSeries::create([
            'title' => 'Old Manga',
            'status' => 'ongoing',
        ]);

        // Force update the timestamp to an older date
        $oldManga->timestamps = false;
        $oldManga->updated_at = now()->subDays(10);
        $oldManga->save();

        sleep(1); // Ensure timestamp difference

        MangaSeries::create([
            'title' => 'New Manga',
            'status' => 'ongoing',
        ]);

        $response = $this->getJson('/api/v1/manga/latest?limit=2');

        $response->assertStatus(200);
        expect($response->json('data.0.title'))->toBe('New Manga');
    });
});

describe('Create Manga', function () {
    it('requires authentication to create manga', function () {
        $response = $this->postJson('/api/v1/manga', [
            'title' => 'New Manga',
        ]);

        $response->assertStatus(401);
    });

    it('authenticated user can create manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $actionGenre = Genre::where('slug', 'action')->first();
        $author = Author::where('slug', 'eiichiro-oda')->first();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/manga', [
                'title' => 'New Manga',
                'description' => 'A new manga series',
                'status' => 'ongoing',
                'author_ids' => [$author->id],
                'genre_ids' => [$actionGenre->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'authors',
                    'genres',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Manga created successfully',
                'data' => [
                    'title' => 'New Manga',
                ],
            ]);

        $this->assertDatabaseHas('manga_series', [
            'title' => 'New Manga',
        ]);
    });

    it('validates required fields when creating manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/manga', []);

        $response->assertStatus(422);
    });

    it('validates status enum when creating manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/manga', [
                'title' => 'New Manga',
                'status' => 'invalid-status',
            ]);

        $response->assertStatus(422);
    });
});

describe('Update Manga', function () {
    it('requires authentication to update manga', function () {
        $manga = MangaSeries::create([
            'title' => 'Original Title',
            'status' => 'ongoing',
        ]);

        $response = $this->putJson("/api/v1/manga/{$manga->slug}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(401);
    });

    it('authenticated user can update manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $manga = MangaSeries::create([
            'title' => 'Original Title',
            'status' => 'ongoing',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/manga/{$manga->slug}", [
                'title' => 'Updated Title',
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Manga updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('manga_series', [
            'id' => $manga->id,
            'title' => 'Updated Title',
            'status' => 'completed',
        ]);
    });

    it('returns 404 when updating non-existent manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/manga/non-existent-slug', [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(404);
    });
});

describe('Delete Manga', function () {
    it('requires authentication to delete manga', function () {
        $manga = MangaSeries::create([
            'title' => 'To Delete',
            'status' => 'ongoing',
        ]);

        $response = $this->deleteJson("/api/v1/manga/{$manga->slug}");

        $response->assertStatus(401);
    });

    it('authenticated user can soft delete manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $manga = MangaSeries::create([
            'title' => 'To Delete',
            'status' => 'ongoing',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/manga/{$manga->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Manga deleted successfully',
            ]);

        // Verify soft delete (record still exists but deleted_at is set)
        $this->assertDatabaseHas('manga_series', [
            'id' => $manga->id,
            'title' => 'To Delete',
        ]);

        expect($manga->fresh()->trashed())->toBeTrue();
    });

    it('returns 404 when deleting non-existent manga', function () {
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assign admin role
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/manga/non-existent-slug');

        $response->assertStatus(404);
    });
});
