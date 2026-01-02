<?php

declare(strict_types=1);

namespace App\Domain\Manga\Services;

use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * MangaService - Business logic for manga operations.
 *
 * Why a service?
 * - Encapsulates complex queries and business rules
 * - Keeps controllers thin
 * - Reusable across different entry points (API, CLI, etc.)
 */
class MangaService
{
    /**
     * Get paginated manga list with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MangaSeries::query();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['genre'])) {
            $query->whereHas('genres', fn (Builder $q) => $q->where('slug', $filters['genre'])
            );
        }

        // Default sort by latest
        $query->latest();

        return $query->paginate($perPage);
    }

    /**
     * Search manga by keyword.
     */
    public function search(string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        return MangaSeries::search($keyword)->paginate($perPage);
    }

    /**
     * Get popular manga (by views).
     */
    public function popular(int $limit = 10): Collection
    {
        return MangaSeries::orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest updated manga.
     */
    public function latest(int $limit = 10): Collection
    {
        return MangaSeries::latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Find manga by slug.
     */
    public function findBySlug(string $slug): ?MangaSeries
    {
        return MangaSeries::where('slug', $slug)->first();
    }

    /**
     * Increment view count.
     */
    public function incrementViews(MangaSeries $manga): void
    {
        $manga->increment('views_count');
    }
}
