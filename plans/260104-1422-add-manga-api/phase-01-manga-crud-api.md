---
parent: ./plan.md
dependencies: []
docs: [../docs/code-standards.md, ../docs/system-architecture.md]
---

# Phase 01: MangaSeries CRUD API

## Overview

| Field | Value |
|-------|-------|
| Date | 2026-01-04 |
| Priority | P1 |
| Effort | 2.5h |
| Implementation | Completed |
| Review | Completed |
| Completed At | 2026-01-04 14:32 |

## Context

Implement full CRUD endpoints for MangaSeries. The domain layer (Model, Service, Actions) already exists. This phase focuses on HTTP layer: Controller, Resources, Requests, Routes.

## Key Insights

1. **MangaSeries model** already has:
   - Relationships: authors, genres, chapters, followers
   - Scopes: `search()` for fulltext/JSON search
   - Soft deletes enabled

2. **MangaService** provides:
   - `list()` - paginated with status/genre filters
   - `search()` - keyword search
   - `popular()` / `latest()` - curated lists
   - `findBySlug()` - single lookup
   - `incrementViews()` - view tracking

3. **Actions** available:
   - `CreateMangaAction` - handles multi-step creation
   - `UpdateMangaAction` - handles updates

## Requirements

### Functional
- List manga with pagination (15 per page default)
- Filter by status, genre
- Search by keyword (title, description, alt_titles)
- Show single manga with relationships loaded
- Create manga (Admin only)
- Update manga (Admin only)
- Soft delete manga (Admin only)

### Non-Functional
- Response time < 100ms for reads
- Consistent JSON structure via ApiResponse
- OpenAPI docs auto-generated

## Architecture

```
Request → Route → Controller → Service/Action → Model → Database
                      ↓
               FormRequest (validation)
                      ↓
               Resource (JSON transform)
```

## Related Code Files

| File | Purpose |
|------|---------|
| `app/Domain/Manga/Models/MangaSeries.php` | Eloquent model |
| `app/Domain/Manga/Services/MangaService.php` | Read operations |
| `app/Domain/Manga/Actions/CreateMangaAction.php` | Create logic |
| `app/Domain/Manga/Actions/UpdateMangaAction.php` | Update logic |
| `app/Http/Controllers/Api/ApiController.php` | Base controller |
| `app/Shared/Traits/ApiResponse.php` | Response helpers |

## Implementation Steps

### Step 1: Create MangaResource
Create `app/Http/Resources/MangaResource.php` to transform MangaSeries model to JSON.

```php
// Fields to include:
// - id, title, alt_titles, slug, description, status
// - cover_image, views_count, average_rating
// - created_at, updated_at
// - authors (when loaded)
// - genres (when loaded)
// - chapters_count (when loaded)
```

### Step 2: Create Form Requests
Create validation for store/update operations.

**StoreMangaRequest.php**:
```php
// Required: title (string, max:255)
// Optional: alt_titles (array), description (text), status (enum)
// Optional: cover_image (string/url), author_ids (array), genre_ids (array)
```

**UpdateMangaRequest.php**:
```php
// All fields optional (PATCH semantics)
// Same validation rules as Store
```

### Step 3: Create MangaController
Create `app/Http/Controllers/Api/V1/MangaController.php` extending ApiController.

Methods:
- `index()` - List with filters
- `show()` - Single manga by slug
- `store()` - Create (Admin)
- `update()` - Update (Admin)
- `destroy()` - Soft delete (Admin)
- `popular()` - Popular list
- `latest()` - Latest list
- `search()` - Search endpoint

### Step 4: Define Routes
Add routes to `routes/api.php` inside v1 group.

```php
// Public routes
Route::get('manga', [MangaController::class, 'index']);
Route::get('manga/popular', [MangaController::class, 'popular']);
Route::get('manga/latest', [MangaController::class, 'latest']);
Route::get('manga/search', [MangaController::class, 'search']);
Route::get('manga/{slug}', [MangaController::class, 'show']);

// Admin routes (auth + role)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('manga', [MangaController::class, 'store']);
    Route::put('manga/{slug}', [MangaController::class, 'update']);
    Route::delete('manga/{slug}', [MangaController::class, 'destroy']);
});
```

### Step 5: Write Tests
Create `tests/Feature/Api/V1/MangaTest.php` with Pest.

Test cases:
- [x] `it can list manga with pagination`
- [x] `it can filter manga by status`
- [x] `it can search manga by keyword`
- [x] `it returns single manga by slug`
- [x] `it returns 404 for non-existent slug`
- [x] `it requires auth to create manga`
- [x] `admin can create manga`
- [x] `admin can update manga`
- [x] `admin can delete manga`

## Todo List

- [x] Create MangaResource.php
- [x] Create StoreMangaRequest.php
- [x] Create UpdateMangaRequest.php
- [x] Create MangaController.php
- [x] Add routes to api.php
- [x] Write MangaTest.php
- [x] Run Pint and fix style
- [x] Run tests and verify pass

## Review Findings

**Resolved:**
- **FIXED**: Missing `role:admin` middleware on store/update/destroy routes
- **FIXED**: SQL injection risk in MangaSeries::search() scope

**Pending:**
- No per_page limit validation (resource exhaustion)
- Missing cover_image URL sanitization (Partially resolved with regex)

## Success Criteria

- [x] `GET /api/v1/manga` returns paginated list
- [x] `GET /api/v1/manga/{slug}` returns single manga with relations
- [x] `POST /api/v1/manga` creates manga (admin only)
- [x] `PUT /api/v1/manga/{slug}` updates manga (admin only)
- [x] `DELETE /api/v1/manga/{slug}` soft deletes (admin only)
- [x] All tests pass
- [x] Pint passes

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Slug conflicts | Low | Medium | Spatie sluggable handles uniqueness |
| N+1 queries | Medium | Medium | Eager load relations in controller |

## Security Considerations

- Admin routes protected with `auth:sanctum` middleware
- Role check via Spatie Permission (future: add `role:admin` middleware)
- Input validation prevents injection attacks
- Soft delete preserves data integrity

## Next Steps

After Phase 01 completion:
1. Move to Phase 02 (Chapter API)
2. Chapters are nested under manga: `/manga/{slug}/chapters`
