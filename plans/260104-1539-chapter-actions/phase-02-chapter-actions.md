# Phase 2: Chapter Actions

> **Goal**: Implement Create, Update, Delete actions for chapters with image handling

## Context

**Current state**: ChapterController has inline logic for create/update. Delete doesn't clean up storage.

**Target state**: Dedicated Action classes that:
- Handle database transactions
- Manage image storage
- Provide rollback on failure

---

## Files to Create

### 1. CreateChapterAction

**Path**: `app/Domain/Manga/Actions/CreateChapterAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * CreateChapterAction - Creates chapter with batch image upload.
 *
 * Why Action pattern?
 * - Single responsibility: only handles chapter creation
 * - Reusable: can be called from controller, CLI, queued job
 * - Testable: easy to mock dependencies
 *
 * Transaction flow:
 * 1. Begin transaction
 * 2. Create chapter record
 * 3. Upload images to storage
 * 4. Create ChapterImage records
 * 5. Commit (or rollback on failure)
 */
class CreateChapterAction
{
    public function __construct(
        private readonly ChapterImageStorageInterface $storage
    ) {}

    /**
     * Create a chapter with images.
     *
     * @param MangaSeries $manga Parent manga
     * @param array{
     *     number: string|float,
     *     title?: string|null,
     *     uploader_id: int,
     *     images?: array<UploadedFile>
     * } $data Chapter data
     * @return Chapter Created chapter with images
     *
     * @throws \Exception If creation fails
     */
    public function __invoke(MangaSeries $manga, array $data): Chapter
    {
        // Collect uploaded paths for rollback if needed
        $uploadedPaths = [];

        try {
            DB::beginTransaction();

            // Step 1: Create chapter record
            $chapter = $manga->chapters()->create([
                'number' => $data['number'],
                'title' => $data['title'] ?? null,
                'uploader_id' => $data['uploader_id'],
                'is_approved' => false, // Always start as pending
            ]);

            // Step 2: Upload and create image records
            if (! empty($data['images'])) {
                $uploadedPaths = $this->storage->storeMany(
                    $data['images'],
                    $manga->id,
                    $chapter->id
                );

                // Create ChapterImage records with order
                foreach ($uploadedPaths as $order => $path) {
                    $chapter->images()->create([
                        'path' => $path,
                        'order' => $order + 1, // 1-indexed for display
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            return $chapter->load(['images', 'uploader']);

        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup uploaded files on failure
            if (! empty($uploadedPaths)) {
                $this->storage->deleteMany($uploadedPaths);
            }

            throw $e;
        }
    }
}
```

---

### 2. UpdateChapterAction

**Path**: `app/Domain/Manga/Actions/UpdateChapterAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use App\Domain\Manga\Models\Chapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * UpdateChapterAction - Updates chapter fields and/or replaces images.
 *
 * Image replacement strategy:
 * - If new images provided: delete old, upload new
 * - If no images provided: keep existing
 * - Partial updates not supported (all or nothing for images)
 *
 * Why all-or-nothing for images?
 * - Simpler logic, fewer edge cases
 * - Users typically re-upload entire chapter for corrections
 * - Matches common manga reader workflows
 */
class UpdateChapterAction
{
    public function __construct(
        private readonly ChapterImageStorageInterface $storage
    ) {}

    /**
     * Update a chapter.
     *
     * @param Chapter $chapter Chapter to update
     * @param array{
     *     number?: string|float,
     *     title?: string|null,
     *     images?: array<UploadedFile>
     * } $data Update data
     * @return Chapter Updated chapter
     *
     * @throws \Exception If update fails
     */
    public function __invoke(Chapter $chapter, array $data): Chapter
    {
        $uploadedPaths = [];
        $oldImagePaths = [];

        try {
            DB::beginTransaction();

            // Step 1: Update chapter fields
            if (isset($data['number'])) {
                $chapter->number = $data['number'];
            }

            if (array_key_exists('title', $data)) {
                $chapter->title = $data['title'];
            }

            $chapter->save();

            // Step 2: Replace images if new ones provided
            if (! empty($data['images'])) {
                // Collect old paths for deletion after successful upload
                $oldImagePaths = $chapter->images->pluck('path')->toArray();

                // Upload new images
                $uploadedPaths = $this->storage->storeMany(
                    $data['images'],
                    $chapter->manga_series_id,
                    $chapter->id
                );

                // Delete old image records
                $chapter->images()->delete();

                // Create new image records
                foreach ($uploadedPaths as $order => $path) {
                    $chapter->images()->create([
                        'path' => $path,
                        'order' => $order + 1,
                    ]);
                }

                // Delete old files from storage (after DB success)
                if (! empty($oldImagePaths)) {
                    $this->storage->deleteMany($oldImagePaths);
                }
            }

            DB::commit();

            return $chapter->fresh(['images', 'uploader']);

        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup newly uploaded files on failure
            if (! empty($uploadedPaths)) {
                $this->storage->deleteMany($uploadedPaths);
            }

            throw $e;
        }
    }
}
```

---

### 3. DeleteChapterAction

**Path**: `app/Domain/Manga/Actions/DeleteChapterAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use App\Domain\Manga\Models\Chapter;
use Illuminate\Support\Facades\DB;

/**
 * DeleteChapterAction - Hard deletes chapter and cleans up storage.
 *
 * Why hard delete?
 * - Chapter model uses SoftDeletes, but this action is for permanent removal
 * - Used by RejectChapterAction and explicit admin deletion
 * - Cleans up storage to prevent orphan files
 *
 * Cleanup order:
 * 1. Delete storage files first (if DB delete fails, files are already gone - acceptable)
 * 2. Force delete chapter record (bypasses soft delete)
 * 3. ChapterImage records cascade via foreign key
 */
class DeleteChapterAction
{
    public function __construct(
        private readonly ChapterImageStorageInterface $storage
    ) {}

    /**
     * Permanently delete a chapter and its images.
     *
     * @param Chapter $chapter Chapter to delete
     * @return bool True if successful
     */
    public function __invoke(Chapter $chapter): bool
    {
        // Get IDs before deletion for storage cleanup
        $mangaId = $chapter->manga_series_id;
        $chapterId = $chapter->id;

        try {
            DB::beginTransaction();

            // Step 1: Delete chapter record (cascades to images via FK)
            // Use forceDelete to bypass SoftDeletes
            $chapter->images()->delete();
            $chapter->forceDelete();

            DB::commit();

            // Step 2: Cleanup storage directory
            // Done after DB commit - if this fails, files are orphaned
            // but data integrity is preserved
            $this->storage->deleteChapterDirectory($mangaId, $chapterId);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
```

---

## Common Pitfalls

1. **Forgetting forceDelete()**: Chapter uses SoftDeletes, so `delete()` won't truly remove it
2. **FK cascade not configured**: Ensure `chapter_images` table has `onDelete('cascade')` on `chapter_id`
3. **Storage cleanup order**: Delete DB records first, then storage. Orphan files < data corruption
4. **Transaction scope**: Don't put storage operations inside transaction - they can't be rolled back

---

## Migration Check

Verify `chapter_images` migration has cascade delete:

```php
// In migration file
$table->foreignId('chapter_id')
    ->constrained()
    ->onDelete('cascade');
```

If not, add a migration to fix it.

---

## Dependency Injection

All actions receive `ChapterImageStorageInterface` via constructor. This is auto-resolved by Laravel's container thanks to the binding in Phase 1.

---

## Key Takeaways

- **Rollback pattern**: Upload first, create records, rollback cleans up files
- **Transaction boundaries**: DB in transaction, storage outside (can't rollback files)
- **forceDelete()**: Required when model uses SoftDeletes but you want permanent removal
- **Order matters**: Collect old paths before delete, cleanup after commit
