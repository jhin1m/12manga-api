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
            return $this->error('Failed to create chapter: '.$e->getMessage(), 500);
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
            return $this->error('Failed to update chapter: '.$e->getMessage(), 500);
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
            return $this->error('Failed to delete chapter: '.$e->getMessage(), 500);
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
            return $this->error('Failed to reject chapter: '.$e->getMessage(), 500);
        }
    }
}
