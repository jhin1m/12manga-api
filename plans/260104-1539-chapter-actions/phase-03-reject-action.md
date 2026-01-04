# Phase 3: Reject Action & Endpoint

> **Goal**: Implement RejectChapterAction and add API endpoint

## Context

**Rejection behavior** (per user requirements): Hard delete chapter and all images.

This is essentially the same as `DeleteChapterAction`, but:
- Semantic difference (reject vs delete)
- May add rejection-specific logic later (notifications, logging)
- Separate endpoint for moderation workflow

---

## Files to Create

### 1. RejectChapterAction

**Path**: `app/Domain/Manga/Actions/RejectChapterAction.php`

```php
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
     * @param Chapter $chapter Chapter to reject
     * @param string|null $reason Optional rejection reason (for future use)
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
```

---

## Files to Modify

### 2. ChapterController - Add reject method

**Path**: `app/Http/Controllers/Api/V1/ChapterController.php`

Add this method to the controller:

```php
use App\Domain\Manga\Actions\RejectChapterAction;

// Update constructor
public function __construct(
    private readonly ChapterService $chapterService,
    private readonly ApproveChapterAction $approveChapter,
    private readonly RejectChapterAction $rejectChapter // Add this
) {}

/**
 * Reject a pending chapter (Admin only).
 *
 * What happens:
 * - Chapter record is permanently deleted
 * - All associated images are removed from storage
 * - ChapterImage records are cascade-deleted
 */
public function reject(Chapter $chapter): JsonResponse
{
    // Validation: Only pending chapters can be rejected
    if ($chapter->is_approved) {
        return $this->error('Cannot reject an approved chapter', 422);
    }

    try {
        ($this->rejectChapter)($chapter);

        return $this->success(null, 'Chapter rejected successfully');
    } catch (\Exception $e) {
        return $this->error('Failed to reject chapter: ' . $e->getMessage(), 500);
    }
}
```

---

### 3. Routes - Add reject endpoint

**Path**: `routes/api.php`

Add inside the admin middleware group:

```php
// Chapter moderation routes
Route::get('chapters/pending', [ChapterController::class, 'pending'])
    ->name('api.v1.chapters.pending');
Route::post('chapters/{chapter}/approve', [ChapterController::class, 'approve'])
    ->name('api.v1.chapters.approve');
Route::post('chapters/{chapter}/reject', [ChapterController::class, 'reject'])  // Add this
    ->name('api.v1.chapters.reject');
```

---

## API Documentation

### Endpoint: Reject Chapter

```
POST /api/v1/chapters/{chapter}/reject
```

**Authorization**: Admin only

**Path Parameters**:
- `chapter` (int): Chapter ID

**Response (200 OK)**:
```json
{
    "success": true,
    "message": "Chapter rejected successfully",
    "data": null
}
```

**Error Responses**:

| Status | Condition |
|--------|-----------|
| 401 | Not authenticated |
| 403 | Not admin role |
| 404 | Chapter not found |
| 422 | Chapter already approved |
| 500 | Deletion failed |

---

## Common Pitfalls

1. **Route model binding**: `{chapter}` uses ID, not slug. Ensure binding works correctly
2. **Approved check timing**: Check in action AND controller (defense in depth)
3. **Error message leak**: Don't expose internal exception messages in production

---

## Testing Considerations

```php
// Test cases for reject endpoint
it('rejects pending chapter and deletes from storage', function () {
    // Create pending chapter with images
    // Call reject endpoint
    // Assert chapter deleted
    // Assert images deleted from storage
});

it('fails to reject approved chapter', function () {
    // Create approved chapter
    // Call reject endpoint
    // Assert 422 error
});

it('requires admin role', function () {
    // Create regular user
    // Call reject endpoint
    // Assert 403 error
});
```

---

## Key Takeaways

- **Delegation pattern**: RejectChapterAction delegates to DeleteChapterAction
- **Semantic separation**: Even if logic is same, separate actions clarify intent
- **Future-proofing**: Rejection may gain additional behavior (notifications, logging)
- **Route binding**: Use model binding for cleaner controller code
