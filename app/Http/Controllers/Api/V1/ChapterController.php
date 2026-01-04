<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Manga\Actions\ApproveChapterAction;
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\Manga\Services\ChapterService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreChapterRequest;
use App\Http\Requests\Api\V1\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChapterController extends ApiController
{
    public function __construct(
        private readonly ChapterService $chapterService,
        private readonly ApproveChapterAction $approveChapter
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
            DB::beginTransaction();

            // Create chapter
            $chapter = $manga->chapters()->create([
                'number' => $validated['number'],
                'title' => $validated['title'] ?? null,
                'uploader_id' => Auth::id(),
                'is_approved' => false, // Default to pending
            ]);

            // Add images if provided
            if (isset($validated['images']) && is_array($validated['images'])) {
                foreach ($validated['images'] as $imageData) {
                    $chapter->images()->create([
                        'path' => $imageData['path'],
                        'order' => $imageData['order'],
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            $chapter->load(['images', 'uploader']);

            return $this->created(
                new ChapterResource($chapter),
                'Chapter created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();

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

        try {
            DB::beginTransaction();

            // Update chapter fields
            if (isset($validated['number'])) {
                // Check for duplicate if number is being changed
                if ($validated['number'] != $chapter->number) {
                    $exists = $manga->chapters()
                        ->where('number', $validated['number'])
                        ->where('id', '!=', $chapter->id)
                        ->exists();

                    if ($exists) {
                        DB::rollBack();

                        return $this->error('Chapter with this number already exists', 422);
                    }
                }
                $chapter->number = $validated['number'];
            }

            if (isset($validated['title'])) {
                $chapter->title = $validated['title'];
            }

            $chapter->save();

            // Update images if provided
            if (isset($validated['images']) && is_array($validated['images'])) {
                // Delete existing images and create new ones
                $chapter->images()->delete();

                foreach ($validated['images'] as $imageData) {
                    $chapter->images()->create([
                        'path' => $imageData['path'],
                        'order' => $imageData['order'],
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            $chapter->load(['images', 'uploader']);

            return $this->success(
                new ChapterResource($chapter),
                'Chapter updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();

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

        $chapter->delete();

        return $this->success(null, 'Chapter deleted successfully');
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
}
