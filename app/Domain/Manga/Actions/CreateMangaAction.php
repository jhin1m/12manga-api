<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Models\MangaSeries;

/**
 * CreateMangaAction - Creates a new manga series.
 *
 * Why an Action?
 * - Single purpose: create manga with all related data
 * - Can be called from controller, CLI, or queue job
 * - Easy to add validation, events, etc.
 */
class CreateMangaAction
{
    /**
     * Create a new manga series.
     *
     * @param array{
     *     title: string,
     *     description?: string,
     *     status?: string,
     *     cover_image?: string,
     *     alt_titles?: array<string, string|array<string>>,
     *     genre_ids?: array<int>,
     *     author_ids?: array<int>
     * } $data
     */
    public function __invoke(array $data): MangaSeries
    {
        // Extract relationship IDs
        $genreIds = $data['genre_ids'] ?? [];
        $authorIds = $data['author_ids'] ?? [];
        unset($data['genre_ids'], $data['author_ids']);

        // Create manga
        $manga = MangaSeries::create($data);

        // Attach relationships
        if (! empty($genreIds)) {
            $manga->genres()->attach($genreIds);
        }

        if (! empty($authorIds)) {
            $manga->authors()->attach($authorIds);
        }

        // Load relationships for response
        return $manga->load(['genres', 'authors']);
    }
}
