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
     * @param  Chapter  $chapter  Chapter to delete
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
