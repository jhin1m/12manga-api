---
parent: ./plan.md
dependencies: [./phase-01-manga-crud-api.md]
docs: [../docs/code-standards.md, ../docs/system-architecture.md]
---

# Phase 03: Genre & Author APIs

## Overview

| Field | Value |
|-------|-------|
| Date | 2026-01-04 |
| Priority | P2 |
| Effort | 2h |
| Implementation | Completed |
| Review | Completed |
| Completed At | 2026-01-04 14:45 |

## Context

Implement read-only APIs for Genres and Authors. These are supporting resources for manga discovery - users browse by genre or find manga by author.

## Key Insights

1. **Genre model**:
   - Simple: id, name, slug
   - Many-to-many with MangaSeries
   - Used for filtering/categorization

2. **Author model**:
   - Simple: id, name, slug
   - Many-to-many with MangaSeries
   - Multiple authors per manga (writer/artist)

3. **Both models**:
   - Use Spatie Sluggable
   - No complex business logic needed
   - Read-only for public (Admin CRUD optional)

## Requirements

### Functional
- List all genres
- Show genre with its manga
- List all authors
- Show author with their manga

### Non-Functional
- Fast response (cached if needed)
- Manga list paginated when nested

## Architecture

Simple CRUD controllers. No nested resources - genres/authors are top-level.

```
GET /api/v1/genres           → List all genres
GET /api/v1/genres/{slug}    → Genre + paginated manga
GET /api/v1/authors          → List all authors
GET /api/v1/authors/{slug}   → Author + paginated manga
```

## Related Code Files

| File | Purpose |
|------|---------|
| `app/Domain/Manga/Models/Genre.php` | Genre model |
| `app/Domain/Manga/Models/Author.php` | Author model |

## Implementation Steps

### Step 1: Create GenreResource and AuthorResource

**GenreResource.php**:
```php
// Fields: id, name, slug
// When detailed: manga_count or mangaSeries (paginated)
```

**AuthorResource.php**:
```php
// Fields: id, name, slug
// When detailed: manga_count or mangaSeries (paginated)
```

### Step 2: Create GenreController

Methods:
- `index()` - List all genres
- `show($slug)` - Genre + its manga (paginated)

```php
public function index(): JsonResponse
{
    $genres = Genre::all();
    return $this->success(GenreResource::collection($genres));
}

public function show(string $slug): JsonResponse
{
    $genre = Genre::where('slug', $slug)->firstOrFail();
    $manga = $genre->mangaSeries()->paginate(15);

    return $this->success([
        'genre' => new GenreResource($genre),
        'manga' => MangaResource::collection($manga),
    ]);
}
```

### Step 3: Create AuthorController

Same pattern as GenreController:
- `index()` - List all authors
- `show($slug)` - Author + their manga (paginated)

### Step 4: Define Routes

```php
// Public routes - no auth required
Route::get('genres', [GenreController::class, 'index']);
Route::get('genres/{slug}', [GenreController::class, 'show']);

Route::get('authors', [AuthorController::class, 'index']);
Route::get('authors/{slug}', [AuthorController::class, 'show']);
```

### Step 5: Write Tests

Test cases:
- [x] `it lists all genres`
- [x] `it shows genre with paginated manga`
- [x] `it returns 404 for non-existent genre`
- [x] `it lists all authors`
- [x] `it shows author with paginated manga`
- [x] `it returns 404 for non-existent author`

## Todo List

- [x] Create GenreResource.php
- [x] Create AuthorResource.php
- [x] Create GenreController.php
- [x] Create AuthorController.php
- [x] Add routes to api.php
- [x] Write GenreTest.php
- [x] Write AuthorTest.php
- [x] Run Pint and fix style
- [x] Run tests and verify pass

## Review Findings

**Resolved:**
- N/A

**Pending:**
- N+1 query in GenreController::show() and AuthorController::show()
- Missing eager loading: `->with(['authors', 'genres'])`
- Hardcoded pagination value (15) - should be configurable

## Success Criteria

- [x] `GET /api/v1/genres` lists all genres
- [x] `GET /api/v1/genres/{slug}` shows genre with manga
- [x] `GET /api/v1/authors` lists all authors
- [x] `GET /api/v1/authors/{slug}` shows author with manga
- [x] All tests pass

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Large manga lists | Medium | Low | Pagination handles this |
| Cache invalidation | Low | Low | Simple data, rarely changes |

## Security Considerations

- Read-only endpoints, no auth required
- No sensitive data exposed
- Consider caching for performance

## Next Steps

After Phase 03:
1. All core Manga APIs complete
2. Consider adding:
   - Admin CRUD for genres/authors
   - Full-text search on author names
   - Genre/Author combination filters

## Unresolved Questions

- Should admin be able to CRUD genres/authors via API, or is it seed data only?
- Do we need manga count on genre/author list, or just on detail view?
