<?php

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\ChapterImage;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    // Seed roles for permission system
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('List Chapters', function () {
    it('lists approved chapters for a manga', function () {
        $manga = MangaSeries::factory()->create();

        // Create approved chapters
        Chapter::factory()
            ->count(3)
            ->approved()
            ->create([
                'manga_series_id' => $manga->id,
                'number' => fn ($attributes) => fake()->unique()->numberBetween(1, 100),
            ]);

        // Create pending chapter (should not appear)
        Chapter::factory()
            ->pending()
            ->create([
                'manga_series_id' => $manga->id,
            ]);

        $response = $this->getJson("/api/v1/manga/{$manga->slug}/chapters");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'number', 'title', 'slug', 'is_approved', 'created_at'],
                ],
            ])
            ->assertJsonCount(3, 'data'); // Only approved chapters
    });

    it('returns empty array when manga has no approved chapters', function () {
        $manga = MangaSeries::factory()->create();

        // Create only pending chapters
        Chapter::factory()
            ->count(2)
            ->pending()
            ->create(['manga_series_id' => $manga->id]);

        $response = $this->getJson("/api/v1/manga/{$manga->slug}/chapters");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for non-existent manga', function () {
        $response = $this->getJson('/api/v1/manga/non-existent-slug/chapters');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Manga not found',
            ]);
    });
});

describe('Show Chapter', function () {
    it('shows chapter with images', function () {
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()
            ->approved()
            ->create([
                'manga_series_id' => $manga->id,
                'number' => '1',
            ]);

        // Create images
        ChapterImage::factory()
            ->count(3)
            ->create([
                'chapter_id' => $chapter->id,
                'order' => fn ($attributes) => fake()->unique()->numberBetween(1, 10),
            ]);

        $response = $this->getJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'number',
                    'title',
                    'slug',
                    'is_approved',
                    'uploader',
                    'images' => [
                        '*' => ['id', 'order', 'path', 'url'],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.images');
    });

    it('hides unapproved chapters from public', function () {
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()
            ->pending()
            ->create([
                'manga_series_id' => $manga->id,
                'number' => '1',
            ]);

        $response = $this->getJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter not found',
            ]);
    });

    it('returns 404 for non-existent chapter', function () {
        $manga = MangaSeries::factory()->create();

        $response = $this->getJson("/api/v1/manga/{$manga->slug}/chapters/999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter not found',
            ]);
    });
});

describe('Create Chapter (Admin)', function () {
    it('admin can create chapter', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => 1,
                'title' => 'Chapter 1',
                'images' => [
                    UploadedFile::fake()->image('page1.jpg'),
                    UploadedFile::fake()->image('page2.jpg'),
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'number',
                    'title',
                    'images',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Chapter created successfully',
                'data' => [
                    'number' => '1.00',
                    'title' => 'Chapter 1',
                    'is_approved' => false, // Default to pending
                ],
            ]);

        $this->assertDatabaseHas('chapters', [
            'manga_series_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapter 1',
            'uploader_id' => $user->id,
        ]);

        $this->assertDatabaseCount('chapter_images', 2);
    });

    it('creates chapter with decimal number', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => 1.5,
                'title' => 'Chapter 1.5',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'number' => '1.50',
                ],
            ]);
    });

    it('fails to create duplicate chapter number', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();

        // Create existing chapter
        Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => 1,
                'title' => 'Duplicate Chapter',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter with this number already exists',
            ]);
    });

    it('fails without authentication', function () {
        $manga = MangaSeries::factory()->create();

        $response = $this->postJson("/api/v1/manga/{$manga->slug}/chapters", [
            'number' => 1,
            'title' => 'Chapter 1',
        ]);

        $response->assertStatus(401);
    });

    it('fails with invalid data', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => -1, // Invalid
            ]);

        $response->assertStatus(422);
    });
});

describe('Update Chapter (Admin)', function () {
    it('admin can update chapter', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
            'title' => 'Old Title',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                ],
            ]);

        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'title' => 'Updated Title',
        ]);
    });

    it('admin can update chapter number', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}", [
                'number' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'number' => '2.00',
                ],
            ]);
    });

    it('fails to update to duplicate number', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();

        Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 2,
        ]);

        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}", [
                'number' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter with this number already exists',
            ]);
    });

    it('fails without authentication', function () {
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->putJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}", [
            'title' => 'Updated',
        ]);

        $response->assertStatus(401);
    });
});

describe('Delete Chapter (Admin)', function () {
    it('admin can delete chapter', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter deleted successfully',
            ]);

        $this->assertDatabaseMissing('chapters', [
            'id' => $chapter->id,
        ]);
    });

    it('fails without authentication', function () {
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()->create([
            'manga_series_id' => $manga->id,
            'number' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/manga/{$manga->slug}/chapters/{$chapter->number}");

        $response->assertStatus(401);
    });
});

describe('Pending Chapters (Admin)', function () {
    it('admin can list pending chapters', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;

        // Create pending chapters
        Chapter::factory()
            ->count(2)
            ->pending()
            ->create();

        // Create approved chapters (should not appear)
        Chapter::factory()
            ->count(3)
            ->approved()
            ->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/chapters/pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Only pending chapters
    });

    it('fails without authentication', function () {
        $response = $this->getJson('/api/v1/chapters/pending');

        $response->assertStatus(401);
    });
});

describe('Approve Chapter (Admin)', function () {
    it('admin can approve pending chapter', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $chapter = Chapter::factory()->pending()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/chapters/{$chapter->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter approved successfully',
                'data' => [
                    'is_approved' => true,
                ],
            ]);

        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'is_approved' => true,
        ]);
    });

    it('fails to approve already approved chapter', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $token = $user->createToken('test-token')->plainTextToken;
        $chapter = Chapter::factory()->approved()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/chapters/{$chapter->id}/approve");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter is already approved',
            ]);
    });

    it('fails without authentication', function () {
        $chapter = Chapter::factory()->pending()->create();

        $response = $this->postJson("/api/v1/chapters/{$chapter->id}/approve");

        $response->assertStatus(401);
    });
});
