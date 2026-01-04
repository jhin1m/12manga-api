<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Manga\Actions\CreateMangaAction;
use App\Domain\Manga\Actions\UpdateMangaAction;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\Manga\Services\MangaService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreMangaRequest;
use App\Http\Requests\Api\V1\UpdateMangaRequest;
use App\Http\Resources\MangaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MangaController extends ApiController
{
    public function __construct(
        private readonly MangaService $mangaService,
        private readonly CreateMangaAction $createManga,
        private readonly UpdateMangaAction $updateManga
    ) {}

    /**
     * List manga with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'genre']);
        $perPage = (int) $request->input('per_page', 15);

        $manga = $this->mangaService->list($filters, $perPage);

        return $this->success(MangaResource::collection($manga));
    }

    /**
     * Show single manga by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)
            ->with(['authors', 'genres'])
            ->withCount('chapters')
            ->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        // Increment views
        $this->mangaService->incrementViews($manga);

        return $this->success(new MangaResource($manga));
    }

    /**
     * Create new manga (Admin only).
     */
    public function store(StoreMangaRequest $request): JsonResponse
    {
        $manga = ($this->createManga)($request->validated());

        return $this->created(new MangaResource($manga), 'Manga created successfully');
    }

    /**
     * Update manga (Admin only).
     */
    public function update(UpdateMangaRequest $request, string $slug): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $manga = ($this->updateManga)($manga, $request->validated());

        return $this->success(new MangaResource($manga), 'Manga updated successfully');
    }

    /**
     * Soft delete manga (Admin only).
     */
    public function destroy(string $slug): JsonResponse
    {
        $manga = MangaSeries::where('slug', $slug)->first();

        if (! $manga) {
            return $this->notFound('Manga not found');
        }

        $manga->delete();

        return $this->success(null, 'Manga deleted successfully');
    }

    /**
     * Get popular manga.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $manga = $this->mangaService->popular($limit);

        return $this->success(MangaResource::collection($manga));
    }

    /**
     * Get latest updated manga.
     */
    public function latest(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $manga = $this->mangaService->latest($limit);

        return $this->success(MangaResource::collection($manga));
    }

    /**
     * Search manga by keyword.
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);

        $manga = $this->mangaService->search($keyword, $perPage);

        return $this->success(MangaResource::collection($manga));
    }
}
