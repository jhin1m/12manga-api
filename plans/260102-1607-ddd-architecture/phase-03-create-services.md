# Phase 03: Create Services Layer

## Context Links
- [Main Plan](./plan.md)
- [Phase 02](./phase-02-move-refactor-models.md)

## Overview

| Field | Value |
|-------|-------|
| Priority | P2 |
| Status | Pending |
| Effort | 45m |

Create Service classes for each domain. Services encapsulate business logic that spans multiple models or requires complex operations.

## Key Insights

**What are Services?**
- Classes containing reusable business logic
- Called by Controllers, Actions, or other Services
- Should NOT contain HTTP-specific logic (no Request/Response)

**When to use Services vs Actions?**
- **Service:** Reusable logic, complex multi-step operations
- **Action:** Single-purpose operation, simpler use case

**Dependency Injection**
- Services should be injected via constructor
- Avoid facades inside services for testability

## Requirements

### Functional
- Create base service structure for each domain
- Include common methods based on README API endpoints

### Non-Functional
- Services must be testable (no static methods)
- Use interfaces for cross-domain dependencies

## Architecture

### Service Structure

```
app/Domain/
├── Manga/
│   └── Services/
│       ├── MangaService.php      # CRUD, search, popular, latest
│       └── ChapterService.php    # Chapter management, approval
│
├── User/
│   └── Services/
│       ├── UserService.php       # Profile management
│       └── FollowService.php     # Follow/unfollow logic
│
└── Community/
    └── Services/
        ├── CommentService.php    # Comment CRUD (placeholder)
        └── RatingService.php     # Rating logic (placeholder)
```

## Related Code Files

**Create:**
- `app/Domain/Manga/Services/MangaService.php`
- `app/Domain/Manga/Services/ChapterService.php`
- `app/Domain/User/Services/UserService.php`
- `app/Domain/User/Services/FollowService.php`
- `app/Domain/Community/Services/CommentService.php`
- `app/Domain/Community/Services/RatingService.php`

## Implementation Steps

### Step 1: Create MangaService

```php
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
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MangaSeries::query();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['genre'])) {
            $query->whereHas('genres', fn (Builder $q) =>
                $q->where('slug', $filters['genre'])
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
```

### Step 2: Create ChapterService

```php
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
```

### Step 3: Create UserService

```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * UserService - Business logic for user operations.
 */
class UserService
{
    /**
     * Update user profile.
     *
     * @param array<string, mixed> $data
     */
    public function updateProfile(User $user, array $data): User
    {
        // Filter allowed fields
        $allowed = ['name', 'bio', 'avatar'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        $user->update($filtered);
        return $user->fresh();
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Find user by profile slug.
     */
    public function findBySlug(string $slug): ?User
    {
        return User::where('profile_slug', $slug)->first();
    }
}
```

### Step 4: Create FollowService

```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * FollowService - Handle follow/unfollow logic.
 */
class FollowService
{
    /**
     * Follow a manga.
     */
    public function follow(User $user, MangaSeries $manga): void
    {
        // Prevent duplicate follows
        if (!$user->followedManga()->where('manga_series_id', $manga->id)->exists()) {
            $user->followedManga()->attach($manga->id);
        }
    }

    /**
     * Unfollow a manga.
     */
    public function unfollow(User $user, MangaSeries $manga): void
    {
        $user->followedManga()->detach($manga->id);
    }

    /**
     * Check if user follows a manga.
     */
    public function isFollowing(User $user, MangaSeries $manga): bool
    {
        return $user->followedManga()->where('manga_series_id', $manga->id)->exists();
    }

    /**
     * Get user's followed manga.
     */
    public function getFollowedManga(User $user): Collection
    {
        return $user->followedManga()
            ->latest('follows.created_at')
            ->get();
    }
}
```

### Step 5: Create Placeholder Community Services

```php
<?php
// app/Domain/Community/Services/CommentService.php

declare(strict_types=1);

namespace App\Domain\Community\Services;

/**
 * CommentService - Placeholder for future comment functionality.
 *
 * TODO: Implement when Comment model is created
 */
class CommentService
{
    // Methods will be added when Comment model exists
}
```

```php
<?php
// app/Domain/Community/Services/RatingService.php

declare(strict_types=1);

namespace App\Domain\Community\Services;

/**
 * RatingService - Placeholder for future rating functionality.
 *
 * TODO: Implement when Rating model is created
 */
class RatingService
{
    // Methods will be added when Rating model exists
}
```

### Step 6: Register Services (Optional - for DI)

If you want to use interfaces, add to `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Services are auto-resolved by Laravel's container
    // Only add bindings if using interfaces
}
```

## Todo List

- [ ] Create MangaService.php
- [ ] Create ChapterService.php
- [ ] Create UserService.php
- [ ] Create FollowService.php
- [ ] Create CommentService.php (placeholder)
- [ ] Create RatingService.php (placeholder)
- [ ] Verify services load without errors
- [ ] Remove .gitkeep files from Services folders

## Success Criteria

- [ ] All service files created
- [ ] No PHP syntax errors
- [ ] Services can be instantiated in tinker

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Over-engineering | Medium | Keep methods simple, add as needed |
| Wrong abstraction | Medium | Start minimal, refactor later |

## Security Considerations

- Services should validate business rules
- Don't expose internal errors to API

## Next Steps

Proceed to [Phase 04: Create Actions](./phase-04-create-actions.md)
