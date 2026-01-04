<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Models\Chapter;

/**
 * RejectChapterAction - Rejects a pending chapter.
 *
 * Current behavior: Hard delete (same as DeleteChapterAction)
 *
 * Why separate action?
 * - Semantic clarity in code (reject vs delete)
 * - Future: Could add notification to uploader
 * - Future: Could log rejection reason
 * - Future: Could soft-reject instead of hard delete
 */
class RejectChapterAction
{
    public function __construct(
        private readonly DeleteChapterAction $deleteAction
    ) {}

    /**
     * Reject a chapter.
     *
     * @param  Chapter  $chapter  Chapter to reject
     * @param  string|null  $reason  Optional rejection reason (for future use)
     * @return bool True if successful
     *
     * Future improvements:
     * - Store rejection reason in audit log
     * - Notify uploader via email/notification
     * - Allow soft-reject with reason display
     */
    public function __invoke(Chapter $chapter, ?string $reason = null): bool
    {
        // Validation: Can only reject pending chapters
        if ($chapter->is_approved) {
            throw new \InvalidArgumentException(
                'Cannot reject an already approved chapter. Use delete instead.'
            );
        }

        // Future: Log rejection reason
        // Future: Notify uploader
        // event(new ChapterRejected($chapter, $reason));

        // Delegate to delete action
        return ($this->deleteAction)($chapter);
    }
}
