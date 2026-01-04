# Project Roadmap

## 📅 Timeline & Status

| Phase | Description | Status | Progress | Est. Completion |
|-------|-------------|--------|----------|-----------------|
| Phase 1 | Foundation & Core Architecture | ✅ Complete | 100% | 2026-01-02 |
| Phase 2 | Manga API (CRUD & Discovery) | ✅ Complete | 100% | 2026-01-04 |
| Phase 3 | User Personalization | 🚧 In Progress | 20% | 2026-01-11 |
| Phase 4 | Community Features | ⏳ Pending | 0% | TBD |
| Phase 5 | Frontend Integration | ⏳ Pending | 0% | TBD |

## 🏗️ Detailed Progress

### Phase 1: Foundation & Core Architecture
- [x] Initial Laravel 12 setup
- [x] DDD Lite directory structure
- [x] Docker environment configuration
- [x] Database schema design for Manga, User, Community domains
- [x] Base API response standardization (ApiResponse trait)
- [x] Authentication foundation (Sanctum)

### Phase 2: Manga API (CRUD & Discovery)
- [x] **Manga CRUD**: Full series management for admins
- [x] **Chapter System**: Nested chapter management and approval workflow
- [x] **Taxonomy**: Genre and Author discovery APIs
- [x] **Discovery**: Popular, Latest, and Full-text Search endpoints
- [x] **Security**: RBAC (Admin role), SQLi protection, and input sanitization
- [x] **Documentation**: Auto-generated API docs via Scramble

### Phase 3: User Personalization
- [ ] User profile management (Bio, Avatar)
- [ ] Follow/Bookmark system for manga series
- [ ] Reading history tracking
- [ ] Personalized recommendations (Basic)
- [ ] Notification system for new chapters

### Phase 4: Community Features
- [ ] Commenting system (nested)
- [ ] Rating system (1-5 stars)
- [ ] Reviews and helpfulness votes
- [ ] Community moderation tools

## 📈 Success Metrics

- **Test Coverage**: Currently **~80%** (Goal: >80% for all domains)
- **API Performance**: Core metadata reads **< 100ms** (Goal: < 100ms)
- **Security**: Zero critical vulnerabilities in production (Goal: Zero)
- **Docs**: 100% endpoint coverage in Scramble (Goal: 100%)

## 📜 Changelog

### [2026-01-04] - Manga API Completion
- **Added**: Full Manga CRUD API (`/api/v1/manga`)
- **Added**: Nested Chapter API (`/api/v1/manga/{slug}/chapters`)
- **Added**: Genre and Author discovery endpoints
- **Added**: Admin role-based authorization for all mutations
- **Security**: Patched SQL injection in search and implemented URL protocol whitelisting
- **Testing**: Reached 62 tests (257 assertions) covering the Manga domain

### [2026-01-02] - Initial DDD Architecture
- **Added**: DDD Lite project structure
- **Added**: Domain models and migrations for Manga, User, and Community
- **Added**: Base `ApiController` and `ApiResponse` traits
- **Added**: Docker Compose setup for development
