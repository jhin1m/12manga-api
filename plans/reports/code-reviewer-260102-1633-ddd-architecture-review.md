# Code Review: DDD Lite Architecture Restructure

**Date**: 2026-01-02
**Reviewer**: Code Review Agent
**Plan**: `/Users/jhin1m/Desktop/ducanh-project/laravel-api-kit/plans/260102-1607-ddd-architecture/plan.md`

---

## Scope

**Files reviewed**: 17 Domain files + 7 integration files
**Lines of code analyzed**: ~1,200 LOC in Domain layer
**Review focus**: DDD Lite architecture implementation, namespace migration, code quality
**Updated plans**: `plans/260102-1607-ddd-architecture/plan.md` (status pending → completed)

---

## Overall Assessment

**Grade**: A- (Excellent with minor improvements needed)

DDD Lite architecture successfully implemented with clean separation of concerns. All models migrated to domain folders, Services and Actions layers properly structured. Tests passing (11 tests, 39 assertions), PSR-12 compliant (69 files pass), zero namespace conflicts detected.

**Strengths**:
- Clean domain boundaries (Manga, User, Community)
- Excellent PHPDoc with "Why" explanations
- Strict types enabled across all files (17/17)
- Zero breaking changes - all tests pass
- Old directories cleanly removed

**Areas for improvement**:
- N+1 query risks in some Service methods
- Placeholder services need implementation plan
- Some Actions could benefit from validation layer

---

## Critical Issues

**None found** ✓

No security vulnerabilities, no data loss risks, no breaking changes detected.

---

## High Priority Findings

### 1. N+1 Query Risk in MangaService

**File**: `app/Domain/Manga/Services/MangaService.php`
**Lines**: 27-44, 58-73

**Issue**: `list()`, `popular()`, `latest()` methods don't eager load relationships.

**Impact**: When serializing manga with genres/authors in API responses, causes N+1 queries.

**Example**:
```php
// Current - N+1 risk
public function popular(int $limit = 10): Collection
{
    return MangaSeries::orderByDesc('views_count')
        ->limit($limit)
        ->get(); // Missing ->with(['genres', 'authors'])
}
```

**Recommendation**:
```php
public function popular(int $limit = 10): Collection
{
    return MangaSeries::with(['genres', 'authors', 'chapters'])
        ->orderByDesc('views_count')
        ->limit($limit)
        ->get();
}
```

**Same issue in**: `latest()`, `list()` methods.

---

### 2. ToggleFollowAction Race Condition

**File**: `app/Domain/User/Actions/ToggleFollowAction.php`
**Lines**: 27-29

**Issue**: `exists()` check then `attach()`/`detach()` creates race condition in concurrent requests.

**Scenario**: User rapidly clicks follow button → duplicate follows or failed unfollows.

**Current**:
```php
$isFollowing = $user->followedManga()
    ->where('manga_series_id', $manga->id)
    ->exists(); // Race condition window here

if ($isFollowing) {
    $user->followedManga()->detach($manga->id);
```

**Recommendation**:
```php
// Use sync() which is idempotent
$wasFollowing = $user->followedManga()
    ->where('manga_series_id', $manga->id)
    ->exists();

if ($wasFollowing) {
    $user->followedManga()->detach($manga->id);
    return ['following' => false, 'message' => 'Unfollowed'];
}

// attach() with duplicate check at DB level via unique constraint
try {
    $user->followedManga()->attach($manga->id);
    return ['following' => true, 'message' => 'Followed'];
} catch (\Illuminate\Database\QueryException $e) {
    // Already following (unique constraint violation)
    return ['following' => true, 'message' => 'Already following'];
}
```

**Note**: Requires unique index on `follows` table `(user_id, manga_series_id)`.

---

### 3. Missing Input Validation in Actions

**Files**: All Action classes (5 files)

**Issue**: Actions accept raw arrays without validation. Controllers should validate, but Actions are reusable (CLI, jobs, etc.).

**Example** - `CreateMangaAction.php`:
```php
public function __invoke(array $data): MangaSeries
{
    // No validation: $data could have invalid genre_ids
    $genreIds = $data['genre_ids'] ?? [];
    $manga->genres()->attach($genreIds); // Could attach non-existent IDs
```

**Recommendation**: Add validation layer or document pre-conditions in PHPDoc:
```php
/**
 * Create a new manga series.
 *
 * @param array{
 *     title: string,
 *     description?: string,
 *     status?: string,
 *     cover_image?: string,
 *     alt_titles?: array<string, string|array<string>>,
 *     genre_ids?: array<int>,  // Must exist in genres table
 *     author_ids?: array<int>  // Must exist in authors table
 * } $data
 * @throws \InvalidArgumentException if genre_ids or author_ids invalid
 */
```

Or use Form Requests in Actions:
```php
public function __invoke(CreateMangaRequest $request): MangaSeries
{
    $data = $request->validated();
    // Now guaranteed valid
}
```

---

## Medium Priority Improvements

### 4. Placeholder Services Need Implementation

**Files**:
- `app/Domain/Community/Services/CommentService.php`
- `app/Domain/Community/Services/RatingService.php`

**Issue**: Empty placeholder classes with TODO comments.

**Current**:
```php
class CommentService
{
    // TODO: Implement when Comment model is created
}
```

**Recommendation**: Either:
1. Remove placeholders until models exist (YAGNI principle)
2. Create GitHub issues/plan for Community domain features
3. Add basic method signatures as interface contract

**Reason**: Empty files add noise to codebase, confuse IDE autocomplete.

---

### 5. ChapterImage URL Generation Assumes Storage Config

**File**: `app/Domain/Manga/Models/ChapterImage.php`
**Lines**: 70-76

**Issue**: Hardcodes fallback logic in model. Config changes require code changes.

```php
public function getUrlAttribute(): string
{
    // Hardcoded logic
    $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
    return Storage::disk($disk)->url($this->path);
}
```

**Recommendation**:
```php
public function getUrlAttribute(): string
{
    // Use configured disk directly
    return Storage::disk(config('filesystems.manga_disk', 'public'))
        ->url($this->path);
}
```

Add to `.env`:
```
MANGA_STORAGE_DISK=public  # or s3, r2, etc.
```

Add to `config/filesystems.php`:
```php
'manga_disk' => env('MANGA_STORAGE_DISK', 'public'),
```

---

### 6. MangaSeries Search Scope Database-Specific

**File**: `app/Domain/Manga/Models/MangaSeries.php`
**Lines**: 150-180

**Issue**: Uses MySQL `MATCH...AGAINST` and `JSON_SEARCH`. Falls back to `LIKE` for SQLite, but implementation differs.

**Current approach**: ✓ Good - has fallback
**Concern**: Fulltext search requires fulltext index on `title, description`.

**Recommendation**: Document index requirement in migration:
```php
// In migration
$table->fullText(['title', 'description']); // MySQL/PostgreSQL
```

Or consider driver-agnostic solution:
- Laravel Scout with database driver
- Dedicated search service (Elasticsearch, Algolia)

---

### 7. FollowService Duplicate Follow Check

**File**: `app/Domain/User/Services/FollowService.php`
**Lines**: 19-24

**Issue**: Similar race condition to `ToggleFollowAction`.

```php
public function follow(User $user, MangaSeries $manga): void
{
    if (! $user->followedManga()->where('manga_series_id', $manga->id)->exists()) {
        $user->followedManga()->attach($manga->id);
    }
}
```

**Recommendation**: Use `syncWithoutDetaching()`:
```php
public function follow(User $user, MangaSeries $manga): void
{
    // Idempotent - no duplicate check needed
    $user->followedManga()->syncWithoutDetaching([$manga->id]);
}
```

---

## Low Priority Suggestions

### 8. Code Style Observations

✓ **Excellent**:
- Strict types enabled (17/17 files)
- PSR-12 compliant (Laravel Pint pass)
- PHPDoc property annotations on all models
- "Why" comments explain design decisions

**Minor**:
- ApiResponse.php missing `declare(strict_types=1)` (line 1-3)
- Some docblocks could add `@throws` annotations

---

### 9. Test Coverage

**Current**: 11 tests, 39 assertions - all pass ✓

**Missing coverage**:
- Domain Services (MangaService, FollowService, etc.)
- Domain Actions (CreateMangaAction, UpdateMangaAction, etc.)
- Model scopes (`search()`, `approved()`, `pending()`)
- Relationship loading

**Recommendation**: Add unit tests for Services/Actions:
```php
// tests/Unit/Domain/Manga/Services/MangaServiceTest.php
test('popular manga includes relationships', function () {
    $manga = MangaSeries::factory()->create();
    $service = new MangaService();

    $popular = $service->popular(10);

    expect($popular->first()->relationLoaded('genres'))->toBeTrue();
});
```

---

### 10. Factory Resolution Pattern

**File**: `app/Domain/User/Models/User.php`
**Lines**: 103-106

**Observation**: Uses `newFactory()` override for factory in different namespace.

```php
protected static function newFactory()
{
    return UserFactory::new();
}
```

✓ **Good**: Solves factory resolution issue
**Alternative**: Configure in `database/factories/UserFactory.php`:
```php
protected $model = \App\Domain\User\Models\User::class;
```

Current approach is fine. Just noting for consistency if other models need factories.

---

## Positive Observations

### Excellent Practices Found:

1. **Domain Boundaries**: Clean separation - Manga, User, Community domains
2. **Service Layer**: Thin controllers, fat services ✓
3. **Action Pattern**: Single-purpose actions with clear responsibilities
4. **Namespace Migration**: 100% complete - no orphaned references
5. **Documentation**: Every model has "Why" comments explaining design
6. **Type Safety**: All files use strict types + proper PHPDoc generics
7. **Slugs**: SEO-friendly URLs throughout (manga, chapters, users)
8. **Soft Deletes**: Proper data retention strategy
9. **Relationship Integrity**: All Eloquent relationships properly typed
10. **Security**: `UpdateProfileAction` filters allowed fields (line 29-32)

### Code Quality Highlights:

**MangaSeries.php** - Excellent PHPDoc:
```php
/**
 * Why these casts?
 * - alt_titles: JSON to PHP array for easy manipulation
 * - average_rating: Decimal string to proper decimal handling
 */
```

**Chapter.php** - Smart slug generation:
```php
// Includes manga title for SEO context
return $mangaTitle.' '.$model->number; // "one-piece-1"
```

**MangaSeeder.php** - Realistic test data with multi-language support:
```php
'alt_titles' => [
    'en' => 'One Piece',
    'ja' => 'ワンピース',
    'vi' => ['Đảo Hải Tặc', 'Vua Hải Tặc'],
],
```

---

## Recommended Actions

### Immediate (Before Production):

1. **Add eager loading to Services** - Fix N+1 queries in MangaService
2. **Add unique constraint** - `follows` table `(user_id, manga_series_id)`
3. **Document validation** - Add PHPDoc pre-conditions to Actions
4. **Add fulltext index** - For `manga_series.title, description` in migration

### Short-term (Next Sprint):

5. **Add Service/Action tests** - Increase coverage from Auth-only to Domain layer
6. **Create Community plan** - Either implement or remove placeholder services
7. **Configure manga storage** - Add `MANGA_STORAGE_DISK` env variable
8. **Fix ToggleFollowAction** - Use idempotent approach for concurrent requests

### Long-term (Nice-to-have):

9. **Consider Scout** - For database-agnostic search
10. **Add monitoring** - Track N+1 queries in production (Debugbar, Telescope)
11. **API Resources** - Create MangaResource, ChapterResource for consistent serialization

---

## Security Considerations

✓ **No vulnerabilities found**

**Positive security patterns**:
- `UpdateProfileAction` filters inputs to allowed fields only (line 19, 29-32)
- All models use `$fillable` (no `$guarded = []` anti-pattern)
- Passwords hashed via `'password' => 'hashed'` cast
- Auth tokens via Sanctum (industry standard)

**Recommendations**:
- Add rate limiting to follow/unfollow endpoints (prevent abuse)
- Consider field-level authorization in Actions (can user update this manga?)
- Add CSRF protection if using Sanctum with SPA

---

## Architecture Review

### DDD Lite Structure: ✓ Correctly Implemented

```
app/Domain/
├── Manga/          ✓ Core business domain
│   ├── Models/     ✓ 5 models (MangaSeries, Chapter, ChapterImage, Genre, Author)
│   ├── Services/   ✓ 2 services (MangaService, ChapterService)
│   └── Actions/    ✓ 3 actions (Create, Update, Approve)
├── User/           ✓ User management domain
│   ├── Models/     ✓ 1 model (User with manga extensions)
│   ├── Services/   ✓ 2 services (UserService, FollowService)
│   └── Actions/    ✓ 2 actions (UpdateProfile, ToggleFollow)
└── Community/      ⚠ Placeholder domain
    └── Services/   ⚠ 2 empty placeholders
```

**Shared Layer**:
```
app/Shared/
└── Traits/
    └── ApiResponse.php  ✓ Moved from app/Traits
```

**Compliance**: ✓ Follows DDD Lite principles
- Bounded contexts clearly defined
- No cyclic dependencies between domains
- Shared code properly extracted
- Infrastructure (Http) separated from Domain

---

## Metrics

| Metric | Value | Status |
|--------|-------|--------|
| Type Coverage | 100% (17/17) | ✓ Excellent |
| Test Coverage | Auth only | ⚠ Needs improvement |
| Linting Issues | 0 | ✓ Pass |
| Tests Passing | 11/11 | ✓ Pass |
| Namespace Conflicts | 0 | ✓ Pass |
| PSR-12 Compliance | 69 files | ✓ Pass |
| TODO Comments | 2 (placeholders) | ⚠ Minor |
| Cyclomatic Complexity | Low | ✓ Good |

---

## Plan Update Status

**Main Plan**: `plans/260102-1607-ddd-architecture/plan.md`

### Phase Completion:

| Phase | Status | Notes |
|-------|--------|-------|
| 1. Create folder structure | ✓ Complete | Domain/ and Shared/ created |
| 2. Move/refactor Models | ✓ Complete | 6 models migrated, old dirs removed |
| 3. Create Services layer | ✓ Complete | 6 services (4 real, 2 placeholders) |
| 4. Create Actions layer | ✓ Complete | 5 actions implemented |
| 5. Update references & test | ✓ Complete | All tests pass, no broken imports |

### Success Criteria:

- [x] All models in domain folders
- [x] All tests passing (11 tests, 39 assertions)
- [x] No broken imports (grep confirmed)
- [x] Services structure ready for business logic
- [x] Actions structure ready for single-purpose operations

**Overall Plan Status**: ✓ **COMPLETE** - All phases executed successfully

---

## Unresolved Questions

1. **Community Domain**: Implement now or defer until Comment/Rating models designed?
2. **Search Strategy**: Keep MySQL fulltext or migrate to Scout/external service?
3. **Storage Strategy**: S3 for production? Need CDN for manga images?
4. **API Versioning**: Current structure supports v1 - plan for v2 breaking changes?
5. **Testing Strategy**: Prefer Feature tests or Unit tests for Services/Actions?

---

## Conclusion

DDD Lite architecture restructure executed excellently. Clean domain boundaries, proper namespace migration, all tests passing, PSR-12 compliant. Code quality high with excellent documentation.

**Main concerns**: N+1 query risks in Services, race conditions in toggle actions, placeholder services. All addressable with minor refactoring.

**Recommendation**: ✅ **APPROVE** with minor improvements before production deployment.

**Next steps**:
1. Address high-priority findings (eager loading, unique constraints)
2. Decide on Community domain strategy
3. Add Service/Action test coverage
4. Update main plan status to "Completed"

---

**Report generated**: 2026-01-02 16:33
**Review duration**: ~25 minutes
**Confidence level**: High (all code paths reviewed, tests verified)
