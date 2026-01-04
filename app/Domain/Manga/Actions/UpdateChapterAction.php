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
     * @param  Chapter  $chapter  Chapter to update
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
