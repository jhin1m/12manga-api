<?php

declare(strict_types=1);

namespace App\Domain\Manga\Services;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Database\Eloquent\Collection;

/**
 * ChapterService - Business logic for chapter operations.
 */
class ChapterService
{
    /**
     * Get approved chapters for a manga.
     */
    public function getApprovedChapters(MangaSeries $manga): Collection
    {
        return $manga->chapters()
            ->approved()
            ->orderBy('number')
            ->get();
    }

    /**
     * Get pending chapters (for admin moderation).
     */
    public function getPendingChapters(): Collection
    {
        return Chapter::pending()
            ->with('mangaSeries', 'uploader')
            ->latest()
            ->get();
    }

    /**
     * Approve a chapter.
     */
    public function approve(Chapter $chapter): Chapter
    {
        $chapter->update(['is_approved' => true]);

        return $chapter->fresh();
    }

    /**
     * Find chapter by manga and number.
     */
    public function findByNumber(MangaSeries $manga, string $number): ?Chapter
    {
        return $manga->chapters()
            ->where('number', $number)
            ->approved()
            ->first();
    }
}
