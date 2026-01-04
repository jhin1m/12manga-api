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
     * @param  MangaSeries  $manga  Parent manga
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
