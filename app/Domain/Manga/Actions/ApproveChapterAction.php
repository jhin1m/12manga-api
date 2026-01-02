<?php

declare(strict_types=1);

namespace App\Domain\Manga\Actions;

use App\Domain\Manga\Models\Chapter;

/**
 * ApproveChapterAction - Approves a pending chapter.
 *
 * Why separate action?
 * - Approval might trigger events (notifications, etc.)
 * - Clear audit trail
 * - Easy to add approval rules later
 */
class ApproveChapterAction
{
    /**
     * Approve a chapter for public display.
     */
    public function __invoke(Chapter $chapter): Chapter
    {
        $chapter->update(['is_approved' => true]);

        // Future: dispatch ChapterApproved event
        // event(new ChapterApproved($chapter));

        return $chapter->fresh();
    }
}
