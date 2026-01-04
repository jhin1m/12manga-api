# Phase 4: Controller Refactor & Tests

> **Goal**: Update controller to use Actions, update validation, add tests

## Context

**Current state**: Controller has inline create/update logic. Request validation expects `path` strings, not file uploads.

**Target state**: Controller delegates to Actions. Validation accepts file uploads.

---

## Files to Modify

### 1. StoreChapterRequest - Update validation

**Path**: `app/Http/Requests/Api/V1/StoreChapterRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Validation rules for chapter creation.
     *
     * Image validation:
     * - array: Multiple files in batch upload
     * - max:100: Limit to 100 pages per chapter
     * - file: Must be an actual uploaded file
     * - image: Must be a valid image type
     * - mimes: Restrict to common web formats
     * - max:5120: 5MB per image (5 * 1024 KB)
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],

            // Image upload validation (replaces path-based approach)
            'images' => ['nullable', 'array', 'max:100'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120', // 5MB per image
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Chapter number is required.',
            'number.numeric' => 'Chapter number must be a valid number.',
            'number.min' => 'Chapter number must be at least 0.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'Maximum 100 images per chapter.',
            'images.*.file' => 'Each image must be a valid file upload.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, WebP, or GIF.',
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
    }
}
```

---

### 2. UpdateChapterRequest - Update validation

**Path**: `app/Http/Requests/Api/V1/UpdateChapterRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for chapter update.
     *
     * All fields optional - partial updates allowed.
     * If images provided, they replace all existing images.
     */
    public function rules(): array
    {
        return [
            'number' => ['sometimes', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],

            // Optional: Replace all images
            'images' => ['nullable', 'array', 'max:100'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'number.numeric' => 'Chapter number must be a valid number.',
            'number.min' => 'Chapter number must be at least 0.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'Maximum 100 images per chapter.',
            'images.*.file' => 'Each image must be a valid file upload.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, WebP, or GIF.',
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
    }
}
```

---

### 3. ChapterController - Refactor to use Actions

**Path**: `app/Http/Controllers/Api/V1/ChapterController.php`

Complete refactored file:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Manga\Actions\ApproveChapterAction;
use App\Domain\Manga\Actions\CreateChapterAction;
use App\Domain\Manga\Actions\DeleteChapterAction;
use App\Domain\Manga\Actions\RejectChapterAction;
use App\Domain\Manga\Actions\UpdateChapterAction;
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\Manga\Services\ChapterService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreChapterRequest;
use App\Http\Requests\Api\V1\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChapterController extends ApiController
{
    public function __construct(
        private readonly ChapterService $chapterService,
        private readonly ApproveChapterAction $approveChapter,
        private readonly CreateChapterAction $createChapter,
        private readonly UpdateChapterAction $updateChapter,
        private readonly DeleteChapterAction $deleteChapter,
        private readonly RejectChapterAction $rejectChapter,
    ) {}

    /**
     * List approved chapters for a manga.
     */
    public function index(string $slug): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $chapters = $this->chapterService->getApprovedChapters($manga);

        return $this->success(ChapterResource::collection($chapters));
    }

    /**
     * Show single chapter with images.
     */
    public function show(string $slug, string $number): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $chapter = $manga->chapters()
            ->where('number', $number)
            ->approved()
            ->with(['images', 'uploader'])
            ->first();

        if (! $chapter) {
            return $this->notFound('Chapter not found');
        }

        return $this->success(new ChapterResource($chapter));
    }

    /**
     * Create new chapter (Admin only).
     *
     * Accepts multipart/form-data with image files.
     */
    public function store(StoreChapterRequest $request, string $slug): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $validated = $request->validated();

        // Check for duplicate chapter number
        $exists = $manga->chapters()
            ->where('number', $validated['number'])
            ->exists();

        if ($exists) {
            return $this->error('Chapter with this number already exists', 422);
        }

        try {
            // Delegate to action
            $chapter = ($this->createChapter)($manga, [
                'number' => $validated['number'],
                'title' => $validated['title'] ?? null,
                'uploader_id' => Auth::id(),
                'images' => $request->file('images', []),
            ]);

            return $this->created(
                new ChapterResource($chapter),
                'Chapter created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create chapter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update chapter (Admin only).
     */
    public function update(UpdateChapterRequest $request, string $slug, string $number): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $chapter = $manga->chapters()->where('number', $number)->first();

        if (! $chapter) {
            return $this->notFound('Chapter not found');
        }

        $validated = $request->validated();

        // Check for duplicate if number is being changed
        if (isset($validated['number']) && $validated['number'] != $chapter->number) {
            $exists = $manga->chapters()
                ->where('number', $validated['number'])
                ->where('id', '!=', $chapter->id)
                ->exists();

            if ($exists) {
                return $this->error('Chapter with this number already exists', 422);
            }
        }

        try {
            // Delegate to action
            $chapter = ($this->updateChapter)($chapter, [
                'number' => $validated['number'] ?? null,
                'title' => $validated['title'] ?? null,
                'images' => $request->file('images'),
            ]);

            return $this->success(
                new ChapterResource($chapter),
                'Chapter updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update chapter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete chapter (Admin only).
     */
    public function destroy(string $slug, string $number): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $chapter = $manga->chapters()->where('number', $number)->first();

        if (! $chapter) {
            return $this->notFound('Chapter not found');
        }

        try {
            ($this->deleteChapter)($chapter);

            return $this->success(null, 'Chapter deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete chapter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List pending chapters for moderation (Admin only).
     */
    public function pending(): JsonResponse
    {
        $chapters = $this->chapterService->getPendingChapters();

        return $this->success(ChapterResource::collection($chapters));
    }

    /**
     * Approve a pending chapter (Admin only).
     */
    public function approve(Chapter $chapter): JsonResponse
    {
        if ($chapter->is_approved) {
            return $this->error('Chapter is already approved', 422);
        }

        $chapter = ($this->approveChapter)($chapter);

        return $this->success(
            new ChapterResource($chapter),
            'Chapter approved successfully'
        );
    }

    /**
     * Reject a pending chapter (Admin only).
     */
    public function reject(Chapter $chapter): JsonResponse
    {
        if ($chapter->is_approved) {
            return $this->error('Cannot reject an approved chapter', 422);
        }

        try {
            ($this->rejectChapter)($chapter);

            return $this->success(null, 'Chapter rejected successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to reject chapter: ' . $e->getMessage(), 500);
        }
    }
}
```

---

## Files to Create

### 4. ChapterActionsTest

**Path**: `tests/Feature/Api/V1/ChapterActionsTest.php`

```php
<?php

declare(strict_types=1);

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\ChapterImage;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

beforeEach(function () {
    // Use fake storage for tests
    Storage::fake('public');
});

describe('CreateChapterAction', function () {
    it('creates chapter with batch image upload', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $manga = MangaSeries::factory()->create();

        $images = [
            UploadedFile::fake()->image('page1.jpg'),
            UploadedFile::fake()->image('page2.jpg'),
            UploadedFile::fake()->image('page3.png'),
        ];

        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => 1,
                'title' => 'Test Chapter',
                'images' => $images,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.number', '1.00')
            ->assertJsonPath('data.title', 'Test Chapter')
            ->assertJsonCount(3, 'data.images');

        // Verify images stored
        $chapter = Chapter::first();
        expect($chapter->images)->toHaveCount(3);

        // Verify files exist in storage
        foreach ($chapter->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    });

    it('orders images by array index', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $manga = MangaSeries::factory()->create();

        $images = [
            UploadedFile::fake()->image('c.jpg'),
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ];

        actingAs($admin, 'sanctum')
            ->postJson("/api/v1/manga/{$manga->slug}/chapters", [
                'number' => 1,
                'images' => $images,
            ]);

        $chapter = Chapter::first();
        $orders = $chapter->images->pluck('order')->toArray();

        expect($orders)->toBe([1, 2, 3]);
    });

    it('rolls back on failure', function () {
        // This test would require mocking storage failure
        // Placeholder for edge case testing
    })->skip('Requires storage mock');
});

describe('UpdateChapterAction', function () {
    it('replaces all images when new images provided', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()
            ->for($manga, 'mangaSeries')
            ->create(['number' => 1]);

        // Create existing images
        ChapterImage::factory()->count(2)->for($chapter)->create();
        $oldCount = $chapter->images()->count();

        // Upload new images
        $newImages = [
            UploadedFile::fake()->image('new1.jpg'),
            UploadedFile::fake()->image('new2.jpg'),
            UploadedFile::fake()->image('new3.jpg'),
        ];

        $response = actingAs($admin, 'sanctum')
            ->putJson("/api/v1/manga/{$manga->slug}/chapters/1", [
                'images' => $newImages,
            ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data.images');

        // Verify old images replaced
        $chapter->refresh();
        expect($chapter->images)->toHaveCount(3);
    });

    it('keeps images when none provided', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()
            ->for($manga, 'mangaSeries')
            ->create(['number' => 1, 'title' => 'Old Title']);

        ChapterImage::factory()->count(2)->for($chapter)->create();

        $response = actingAs($admin, 'sanctum')
            ->putJson("/api/v1/manga/{$manga->slug}/chapters/1", [
                'title' => 'New Title',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonCount(2, 'data.images');
    });
});

describe('DeleteChapterAction', function () {
    it('deletes chapter and cleans up storage', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $manga = MangaSeries::factory()->create();
        $chapter = Chapter::factory()
            ->for($manga, 'mangaSeries')
            ->create(['number' => 1]);

        // Create image with actual storage
        $path = "chapters/{$manga->id}/{$chapter->id}/001.jpg";
        Storage::disk('public')->put($path, 'fake content');
        ChapterImage::factory()->for($chapter)->create(['path' => $path]);

        $response = actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/manga/{$manga->slug}/chapters/1");

        $response->assertOk();

        // Verify chapter deleted (including soft-deleted check)
        expect(Chapter::withTrashed()->find($chapter->id))->toBeNull();

        // Verify storage cleaned
        Storage::disk('public')->assertMissing($path);
    });
});

describe('RejectChapterAction', function () {
    it('rejects pending chapter', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $chapter = Chapter::factory()->create(['is_approved' => false]);

        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/chapters/{$chapter->id}/reject");

        $response->assertOk()
            ->assertJsonPath('message', 'Chapter rejected successfully');

        expect(Chapter::withTrashed()->find($chapter->id))->toBeNull();
    });

    it('fails to reject approved chapter', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $chapter = Chapter::factory()->create(['is_approved' => true]);

        $response = actingAs($admin, 'sanctum')
            ->postJson("/api/v1/chapters/{$chapter->id}/reject");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot reject an approved chapter');
    });

    it('requires admin role', function () {
        $user = User::factory()->create();
        $chapter = Chapter::factory()->create(['is_approved' => false]);

        $response = actingAs($user, 'sanctum')
            ->postJson("/api/v1/chapters/{$chapter->id}/reject");

        $response->assertForbidden();
    });
});
```

---

## Verification Steps

```bash
# 1. Run Laravel Pint
./vendor/bin/pint

# 2. Run all tests
./vendor/bin/pest

# 3. Run specific test file
./vendor/bin/pest tests/Feature/Api/V1/ChapterActionsTest.php

# 4. Manual test with curl
curl -X POST http://localhost:8080/api/v1/manga/one-piece/chapters \
  -H "Authorization: Bearer {token}" \
  -F "number=1" \
  -F "title=Test Chapter" \
  -F "images[]=@page1.jpg" \
  -F "images[]=@page2.jpg"
```

---

## Common Pitfalls

1. **Multipart form data**: Must use `multipart/form-data` for file uploads, not JSON
2. **$request->file() vs validated()**: Use `$request->file('images')` for uploaded files
3. **Storage fake in tests**: Remember `Storage::fake()` in beforeEach
4. **Role assignment**: Tests need `$admin->assignRole('admin')`

---

## Key Takeaways

- **Controller is thin**: Just routing, validation, delegation, response
- **Actions contain logic**: Business rules live in Action classes
- **File uploads**: Use `$request->file()` not `$request->validated()`
- **Test isolation**: Fake storage prevents test pollution
