# Plan: Implement Remaining Chapter Actions

> **Date**: 2026-01-04
> **Priority**: P1 (Critical)
> **Estimated Phases**: 4

## Overview

Implement 4 Chapter Actions to handle image uploads, updates, deletions, and moderation with storage abstraction supporting both S3 and local filesystem.

### Current State

- **Chapter/ChapterImage models**: Exist with proper relationships
- **ChapterController**: Has CRUD but logic is inline (not in Actions)
- **Storage**: Config supports S3 and local, but no upload handling exists
- **Gap**: Controller creates image records from paths, doesn't handle actual file uploads

### Target State

- Dedicated Action classes for Create/Update/Delete/Reject
- Storage service abstracting S3/local with auto-detection
- Batch image upload with auto-ordering
- Proper cleanup on delete/reject operations

---

## Architecture Decision

### Why Actions over Services?

| Aspect | Action Pattern | Service Pattern |
|--------|---------------|-----------------|
| **Purpose** | Single write operation | Multiple reads/complex queries |
| **Scope** | One thing well | Cross-model coordination |
| **Existing pattern** | `ApproveChapterAction` exists | `ChapterService` for reads |

**Decision**: Use Actions for write operations (create/update/delete/reject) to match existing pattern.

### Storage Abstraction Approach

```
┌─────────────────────────────────────────┐
│           ChapterImageStorage           │  ← Interface
├─────────────────────────────────────────┤
│  + store(files): array                  │
│  + delete(paths): bool                  │
│  + getUrl(path): string                 │
│  + getDisk(): string                    │
└─────────────────────────────────────────┘
          ▲
          │ implements
┌─────────────────────────────────────────┐
│     ChapterImageStorageService          │  ← Concrete
├─────────────────────────────────────────┤
│  - Uses FILESYSTEM_DISK env var         │
│  - Auto-detects s3 vs public disk       │
│  - Stores to: chapters/{manga}/{chapter}│
└─────────────────────────────────────────┘
```

---

## File Changes Summary

### New Files (7)

| File | Purpose |
|------|---------|
| `app/Domain/Manga/Contracts/ChapterImageStorageInterface.php` | Storage abstraction interface |
| `app/Domain/Manga/Services/ChapterImageStorageService.php` | Concrete storage implementation |
| `app/Domain/Manga/Actions/CreateChapterAction.php` | Handle chapter + image batch upload |
| `app/Domain/Manga/Actions/UpdateChapterAction.php` | Handle chapter update + image re-order |
| `app/Domain/Manga/Actions/DeleteChapterAction.php` | Hard delete chapter + cleanup images |
| `app/Domain/Manga/Actions/RejectChapterAction.php` | Reject = hard delete (per requirements) |
| `tests/Feature/Api/V1/ChapterActionsTest.php` | Tests for all actions |

### Modified Files (4)

| File | Change |
|------|--------|
| `app/Http/Controllers/Api/V1/ChapterController.php` | Delegate to Actions |
| `app/Http/Requests/Api/V1/StoreChapterRequest.php` | Add file upload validation |
| `app/Http/Requests/Api/V1/UpdateChapterRequest.php` | Add file upload validation |
| `routes/api.php` | Add reject endpoint |

---

## Phases

### Phase 1: Storage Abstraction

Create the storage interface and service to handle S3/local transparently.

**See**: `phase-01-storage-abstraction.md`

### Phase 2: Chapter Actions

Implement CreateChapterAction, UpdateChapterAction, DeleteChapterAction.

**See**: `phase-02-chapter-actions.md`

### Phase 3: Reject Action & Endpoint

Implement RejectChapterAction and wire up the API endpoint.

**See**: `phase-03-reject-action.md`

### Phase 4: Controller Refactor & Tests

Update controller to use Actions, update request validation, add tests.

**See**: `phase-04-controller-refactor-tests.md`

---

## Implementation Order

```
Phase 1 ─────► Phase 2 ─────► Phase 3 ─────► Phase 4
Storage        Actions        Reject         Refactor
                              Endpoint       + Tests
```

Dependencies:
- Phase 2 depends on Phase 1 (storage service)
- Phase 3 depends on Phase 2 (DeleteChapterAction pattern)
- Phase 4 depends on all previous phases

---

## Key Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Storage | S3 + Local via abstraction | User requested both |
| Rejection | Hard delete | User preference |
| Upload | Batch with auto-order | User preference |
| Image ordering | By array index | Simpler than filename parsing |
| Path format | `chapters/{manga_id}/{chapter_id}/` | Organized, easy cleanup |

---

## Validation Rules for Images

```php
// StoreChapterRequest - updated rules
'images' => ['nullable', 'array', 'max:100'],
'images.*' => ['file', 'image', 'mimes:jpeg,png,webp', 'max:5120'], // 5MB each
```

---

## Error Handling Strategy

1. **Transaction wrapping**: All DB operations in transaction
2. **Storage rollback**: If image upload fails, cleanup already-uploaded files
3. **Validation first**: Validate all images before uploading any
4. **Clear error messages**: Return specific error for failed image

---

## Unresolved Questions

1. **Image processing**: Should images be resized/optimized during upload? (Not in scope for now)
2. **Temporary uploads**: No temp upload strategy - files uploaded directly to permanent storage

---

## Success Criteria

- [ ] `CreateChapterAction` handles batch image upload
- [ ] `UpdateChapterAction` supports image replacement
- [ ] `DeleteChapterAction` removes chapter + cleans up storage
- [ ] `RejectChapterAction` hard deletes with cleanup
- [ ] `POST /chapters/{id}/reject` endpoint works
- [ ] Storage works with both S3 and local filesystem
- [ ] All existing tests still pass
- [ ] New tests cover action functionality
