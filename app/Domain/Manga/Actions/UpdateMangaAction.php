<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Models\MangaSeries;

/**
 * UpdateMangaAction - Updates an existing manga series.
 */
class UpdateMangaAction
{
    /**
     * Update manga series.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(MangaSeries $manga, array $data): MangaSeries
    {
        // Extract relationship IDs
        $genreIds = $data['genre_ids'] ?? null;
        $authorIds = $data['author_ids'] ?? null;
        unset($data['genre_ids'], $data['author_ids']);

        // Update manga fields
        $manga->update($data);

        // Sync relationships (if provided)
        if ($genreIds !== null) {
            $manga->genres()->sync($genreIds);
        }

        if ($authorIds !== null) {
            $manga->authors()->sync($authorIds);
        }

        return $manga->fresh(['genres', 'authors']);
    }
}
