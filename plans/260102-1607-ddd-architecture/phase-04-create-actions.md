# Phase 04: Create Actions Layer

## Context Links
- [Main Plan](./plan.md)
- [Phase 03](./phase-03-create-services.md)

## Overview

| Field | Value |
|-------|-------|
| Priority | P2 |
| Status | Pending |
| Effort | 30m |

Create Action classes for single-purpose operations. Actions are invocable classes that handle one specific task.

## Key Insights

**What are Actions?**
- Single-responsibility classes (one `__invoke` method)
- Perfect for operations called from controllers
- Easy to test in isolation
- Self-documenting: class name describes what it does

**Action vs Service:**
- **Action:** `CreateManga`, `ApprovChapter` - one specific operation
- **Service:** `MangaService` - collection of related operations

**Laravel Pattern:**
Actions are invocable classes using `__invoke()` magic method:
```php
$action = new CreateManga();
$manga = $action($data);  // Calls __invoke
```

## Requirements

### Functional
- Create Actions for common manga operations
- Create Actions for user operations
- Follow single-responsibility principle

### Non-Functional
- Use type hints for all parameters and returns
- Keep actions focused and simple

## Architecture

```
app/Domain/
├── Manga/
│   └── Actions/
│       ├── CreateMangaAction.php
│       ├── UpdateMangaAction.php
│       └── ApproveChapterAction.php
│
├── User/
│   └── Actions/
│       ├── UpdateProfileAction.php
│       └── ToggleFollowAction.php
│
└── Community/
    └── Actions/
        └── .gitkeep  # Placeholder
```

## Related Code Files

**Create:**
- `app/Domain/Manga/Actions/CreateMangaAction.php`
- `app/Domain/Manga/Actions/UpdateMangaAction.php`
- `app/Domain/Manga/Actions/ApproveChapterAction.php`
- `app/Domain/User/Actions/UpdateProfileAction.php`
- `app/Domain/User/Actions/ToggleFollowAction.php`

## Implementation Steps

### Step 1: Create CreateMangaAction

```php
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
        if (!empty($genreIds)) {
            $manga->genres()->attach($genreIds);
        }

        if (!empty($authorIds)) {
            $manga->authors()->attach($authorIds);
        }

        // Load relationships for response
        return $manga->load(['genres', 'authors']);
    }
}
```

### Step 2: Create UpdateMangaAction

```php
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
     * @param array<string, mixed> $data
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
```

### Step 3: Create ApproveChapterAction

```php
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
```

### Step 4: Create UpdateProfileAction

```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;

/**
 * UpdateProfileAction - Updates user profile information.
 */
class UpdateProfileAction
{
    /**
     * Allowed profile fields.
     *
     * @var array<int, string>
     */
    private const ALLOWED_FIELDS = ['name', 'bio', 'avatar'];

    /**
     * Update user profile.
     *
     * @param array<string, mixed> $data
     */
    public function __invoke(User $user, array $data): User
    {
        // Filter to allowed fields only (security)
        $filtered = array_intersect_key(
            $data,
            array_flip(self::ALLOWED_FIELDS)
        );

        $user->update($filtered);

        return $user->fresh();
    }
}
```

### Step 5: Create ToggleFollowAction

```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;

/**
 * ToggleFollowAction - Toggle follow status for a manga.
 *
 * Why toggle instead of separate follow/unfollow?
 * - Single endpoint: POST /manga/{slug}/follow
 * - Returns new state to client
 * - Simpler API
 */
class ToggleFollowAction
{
    /**
     * Toggle follow status.
     *
     * @return array{following: bool, message: string}
     */
    public function __invoke(User $user, MangaSeries $manga): array
    {
        $isFollowing = $user->followedManga()
            ->where('manga_series_id', $manga->id)
            ->exists();

        if ($isFollowing) {
            $user->followedManga()->detach($manga->id);
            return [
                'following' => false,
                'message' => 'Unfollowed successfully',
            ];
        }

        $user->followedManga()->attach($manga->id);
        return [
            'following' => true,
            'message' => 'Followed successfully',
        ];
    }
}
```

## Usage Example

How controllers will use Actions:

```php
// In MangaController
public function store(StoreMangaRequest $request, CreateMangaAction $action)
{
    $manga = $action($request->validated());
    return new MangaResource($manga);
}

// In FollowController
public function toggle(
    MangaSeries $manga,
    ToggleFollowAction $action
) {
    $result = $action(auth()->user(), $manga);
    return response()->json($result);
}
```

## Todo List

- [ ] Create CreateMangaAction.php
- [ ] Create UpdateMangaAction.php
- [ ] Create ApproveChapterAction.php
- [ ] Create UpdateProfileAction.php
- [ ] Create ToggleFollowAction.php
- [ ] Verify actions load without errors
- [ ] Remove .gitkeep files from Actions folders

## Success Criteria

- [ ] All action files created
- [ ] No PHP syntax errors
- [ ] Actions can be instantiated and invoked

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Duplication with Services | Low | Actions call Services if complex |
| Over-abstraction | Medium | Only create Actions for controller operations |

## Security Considerations

- Actions should validate data before processing
- Use ALLOWED_FIELDS pattern to prevent mass assignment

## Next Steps

Proceed to [Phase 05: Update References and Test](./phase-05-update-references-test.md)
