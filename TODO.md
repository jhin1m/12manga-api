# TODO - Manga Reader API

> Missing features for Manga and Chapter domains based on PDR requirements.
> Last updated: 2026-01-04

## Priority Legend
- 🔴 **P1** - Critical, blocks core functionality
- 🟡 **P2** - Important, needed for MVP
- 🟢 **P3** - Nice-to-have, can defer

---

## ✅ Completed Features (Phase 2 - API Core)

### Manga Domain - CRUD API
- ✅ `GET /manga` - List manga with filters
- ✅ `GET /manga/popular` - Popular manga
- ✅ `GET /manga/latest` - Latest updates
- ✅ `GET /manga/search` - Search functionality
- ✅ `GET /manga/{slug}` - Manga details
- ✅ `POST /manga` - Create manga (admin)
- ✅ `PUT /manga/{slug}` - Update manga (admin)
- ✅ `DELETE /manga/{slug}` - Delete manga (admin)
- ✅ `MangaController` - Implemented
- ✅ `MangaResource` - Implemented
- ✅ `StoreMangaRequest` - Validation
- ✅ `UpdateMangaRequest` - Validation

### Chapter Domain - CRUD API
- ✅ `GET /manga/{slug}/chapters` - List chapters
- ✅ `GET /manga/{slug}/chapters/{number}` - Chapter detail with images
- ✅ `POST /manga/{slug}/chapters` - Create chapter (admin)
- ✅ `PUT /manga/{slug}/chapters/{number}` - Update chapter (admin)
- ✅ `DELETE /manga/{slug}/chapters/{number}` - Delete chapter (admin)
- ✅ `GET /chapters/pending` - List pending chapters (admin)
- ✅ `POST /chapters/{chapter}/approve` - Approve chapter (admin)
- ✅ `ChapterController` - Implemented
- ✅ `ChapterResource` - Implemented
- ✅ `ChapterImageResource` - Implemented
- ✅ `StoreChapterRequest` - Validation
- ✅ `UpdateChapterRequest` - Validation

### Genre Domain - Public API
- ✅ `GET /genres` - List genres
- ✅ `GET /genres/{slug}` - Genre details with manga
- ✅ `GenreController` - Implemented
- ✅ `GenreResource` - Implemented

### Author Domain - Public API
- ✅ `GET /authors` - List authors
- ✅ `GET /authors/{slug}` - Author details with manga
- ✅ `AuthorController` - Implemented
- ✅ `AuthorResource` - Implemented

### Infrastructure & Security
- ✅ Role-based middleware (`role:admin`) - Implemented
- ✅ SQL injection prevention - Fixed in MangaSeries search
- ✅ Input sanitization - Request validation layer
- ✅ Factory classes - MangaSeries, Chapter, ChapterImage
- ✅ Comprehensive test suite - 4 feature test files

---

## 📖 Chapter Domain (P1 - Remaining Tasks)

### 🔴 Chapter Upload Actions
| Task | Status | Notes |
|------|--------|-------|
| `CreateChapterAction` creation | ⬜ | Handle images upload + ordering |
| `UpdateChapterAction` creation | ⬜ | Handle images re-ordering |
| `DeleteChapterAction` creation | ⬜ | Cleanup images from storage |

### 🟡 Chapter Moderation
| Task | Status | Notes |
|------|--------|-------|
| `RejectChapterAction` creation | ⬜ | Delete or mark as rejected |
| Reject endpoint `POST /chapters/{id}/reject` | ⬜ | Hook to `RejectChapterAction` |

---

## 📚 Manga Domain (P2 - Enhancement)

### 🟡 Manga Discovery Features
| Task | Status | Notes |
|------|--------|-------|
| Random manga endpoint `GET /manga/random` | ⬜ | For discovery feature |

### 🟢 Advanced Filtering (Future)
| Task | Status | Notes |
|------|--------|-------|
| Spatie Query Builder integration | ⬜ | Replace manual filters in `MangaService::list()` |
| Sort by: views, rating, updated_at, created_at | ⬜ | - |
| Filter by: multiple genres, status, year | ⬜ | - |
| Include relations param | ⬜ | `?include=authors,genres,chapters` |

---

## 👤 User Domain (P2 - Critical)

Models and services exist but **NO API endpoints**.

### 🟡 Follow System API
| Task | Status | Notes |
|------|--------|-------|
| `POST /manga/{slug}/follow` - Follow manga | ⬜ | Use `FollowService::follow()` |
| `DELETE /manga/{slug}/follow` - Unfollow manga | ⬜ | Use `FollowService::unfollow()` |
| `GET /user/follows` - List followed manga | ⬜ | Use `FollowService::getFollowedManga()` |
| `GET /manga/{slug}` - Include `is_following` flag | ⬜ | For authenticated users |
| `FollowController` creation | ⬜ | - |

### 🟡 User Profile API
| Task | Status | Notes |
|------|--------|-------|
| `GET /user/profile` - Get profile | ⬜ | Avatar, bio, stats |
| `PUT /user/profile` - Update profile | ⬜ | Use `UpdateProfileAction` (exists) |
| `POST /user/avatar` - Upload avatar | ⬜ | Image upload handling |
| `UserController` creation | ⬜ | - |
| `ProfileResource` creation | ⬜ | - |

### 🟢 Reading Progress API (Planned)
| Task | Status | Notes |
|------|--------|-------|
| `reading_progress` migration | ⬜ | `user_id, manga_id, chapter_id, page` |
| `ReadingProgress` model | ⬜ | - |
| `POST /manga/{slug}/progress` - Save progress | ⬜ | - |
| `GET /manga/{slug}/progress` - Get progress | ⬜ | - |
| `GET /user/history` - Reading history | ⬜ | Recent reads |
| `ReadingProgressService` creation | ⬜ | - |

---

## 💬 Community Domain (P3 - Planned)

Placeholder services exist. **NO models or migrations**.

### 🟢 Comments System
| Task | Status | Notes |
|------|--------|-------|
| `comments` migration | ⬜ | Polymorphic for manga/chapter |
| `Comment` model | ⬜ | Nested/threaded support |
| `POST /manga/{slug}/comments` - Add comment | ⬜ | - |
| `GET /manga/{slug}/comments` - List comments | ⬜ | Paginated, sorted |
| `DELETE /comments/{id}` - Delete comment | ⬜ | Owner or admin |
| `CommentController` creation | ⬜ | - |
| `CommentResource` creation | ⬜ | - |
| Implement `CommentService` | ⬜ | Currently placeholder |

### 🟢 Ratings System
| Task | Status | Notes |
|------|--------|-------|
| `ratings` migration | ⬜ | `user_id, manga_id, score (1-5)` |
| `Rating` model | ⬜ | - |
| `POST /manga/{slug}/rating` - Rate manga | ⬜ | Upsert |
| `GET /manga/{slug}/rating` - Get user's rating | ⬜ | - |
| Update `MangaSeries::rating` average | ⬜ | On rating change |
| Implement `RatingService` | ⬜ | Currently placeholder |

### 🟢 Reviews System (Future)
| Task | Status | Notes |
|------|--------|-------|
| `reviews` migration | ⬜ | Extended rating with text |
| `Review` model | ⬜ | - |
| Helpfulness votes | ⬜ | - |

---

## 🧪 Testing (Ongoing)

| Task | Status | Notes |
|------|--------|-------|
| `MangaControllerTest` - All endpoints | ✅ | Implemented |
| `ChapterControllerTest` - All endpoints | ✅ | Implemented |
| `GenreControllerTest` - All endpoints | ✅ | Implemented |
| `AuthorControllerTest` - All endpoints | ✅ | Implemented |
| `FollowTest` - Follow/unfollow flow | ⬜ | After endpoints |
| `AuthTest` - Login/register/logout | ✅ | Exists |
| Target: 80% coverage | ⬜ | Per PDR requirement |

---

## 🔧 Technical Debt

| Task | Status | Notes |
|------|--------|-------|
| Admin role middleware | ✅ | `role:admin` implemented |
| Image storage config | ⬜ | S3 vs local decision |
| API caching layer | ⬜ | Redis for popular/latest endpoints |
| Rate limit tuning | ⬜ | Per-endpoint limits |
| Image upload implementation | ⬜ | Cover/avatar/chapter images |

---

## Implementation Order (Recommended)

1. ~~**Phase 1**: Database Foundation~~ ✅ **DONE**
2. ~~**Phase 2**: API Core (Manga, Chapter, Genre, Author CRUD)~~ ✅ **DONE**
3. **Phase 3**: User Domain API (P2) - **NEXT**
   - Follow System
   - User Profile
   - Reading Progress
4. **Phase 4**: Chapter Upload Actions (P1) - Admin workflow
5. **Phase 5**: Image Upload System (P2) - Cover, avatar, chapter images
6. **Phase 6**: Advanced Features (P2) - Discovery, filtering
7. **Phase 7**: Community Features (P3) - Comments, ratings

---

## Unresolved Questions

1. **Image Storage**: S3 or local filesystem for chapter images?
2. **Image URLs**: Signed URLs for private storage or public URLs?
3. **UGC Support**: Should users be able to upload manga series (not just admins)?
4. **Notifications**: Real-time (WebSocket) or email-based for new chapter releases?
5. **Moderation**: Auto-approve trusted uploaders or always require approval?

---

## Implementation Summary

### What We've Built (Phase 2)
- **20 API Endpoints** across 4 controllers (Manga, Chapter, Genre, Author)
- **8 API Resources** for data transformation
- **6 Form Requests** for validation
- **3 Factories** for testing
- **4 Feature Test Suites** with comprehensive coverage
- **RBAC Middleware** for admin routes
- **Security Fixes** (SQL injection, input validation)

### What's Next (Phase 3)
Focus on **User Domain** to enable core user experience:
- Follow/Unfollow manga series
- User profiles with avatars
- Reading history tracking
- Continue reading functionality
