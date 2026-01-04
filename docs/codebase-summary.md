# Codebase Summary

## 🗺️ High-Level Overview
This project is built on **Laravel 12** and adopts a **DDD Lite** (Domain-Driven Design) architecture. Instead of traditional Laravel layering (Controllers -> Models), we group logic into **Domains** representing business capabilities.

## 📂 Directory Structure

```text
app/
├── Domain/               # Core Business Logic (The "What")
│   ├── Manga/            # Manga, Chapters, Genres, Authors
│   │   ├── Actions/      # CreateMangaAction, UpdateMangaAction, ApproveChapterAction
│   │   ├── Models/       # MangaSeries, Chapter, ChapterImage, Genre, Author
│   │   └── Services/     # MangaService, ChapterService
│   ├── User/             # Users, Profiles, Follows
│   └── Community/        # Comments, Ratings (Services implemented)
├── Http/                 # API Delivery Layer (The "How")
│   ├── Controllers/Api/V1/ # MangaController, ChapterController, GenreController, AuthorController
│   ├── Middleware/       # EnsureUserHasRole
│   ├── Requests/Api/V1/  # StoreMangaRequest, UpdateMangaRequest, StoreChapterRequest, UpdateChapterRequest
│   └── Resources/        # MangaResource, ChapterResource, GenreResource, AuthorResource, etc.
├── Shared/               # Cross-cutting concerns
│   └── Traits/           # Common utilities like ApiResponse
├── Providers/            # Laravel Service Providers
└── Console/              # CLI Commands
```

## 🧩 Domain Completeness

### Manga Domain (100% Complete)
- **Entities**: All core entities (`MangaSeries`, `Chapter`, `Genre`, `Author`) implemented with migrations and relationships.
- **Business Logic**: Write operations (Actions) and complex reads (Services) fully implemented.
- **API Coverage**: Full CRUD for Manga and Chapters, supporting APIs for Genres and Authors.
- **Security**: RBAC enforced on all mutation endpoints; input sanitization and SQLi protection in place.
- **Testing**: Comprehensive feature tests covering all endpoints, authentication, and validation scenarios.


## 🏗️ File Organization Conventions

### 1. Domain Layer (`app/Domain/{DomainName}`)
- **Models/**: Eloquent models specific to this domain.
- **Services/**: Classes containing business logic that doesn't fit in a single model or action.
- **Actions/**: Single-responsibility classes for complex write operations (e.g., `CreateMangaAction`).

### 2. HTTP Layer (`app/Http`)
- **Controllers/Api/V1/**: Versioned controllers. They should remain "thin," delegating logic to Domain Services or Actions.
- **Requests/Api/V1/**: Validation rules for incoming data.
- **Resources/**: Definitions for how models are serialized into JSON responses.

### 3. Testing (`tests/`)
- **Feature/Api/V1/**: Integration tests for API endpoints.
- **Feature/Domain/**: Business logic tests for Services and Actions.
- **Unit/**: Pure unit tests for helpers or small logic units.

## 🔑 Key Files & Roles
- **`app/Http/Controllers/Api/ApiController.php`**: Base controller providing the `ApiResponse` trait to all API controllers.
- **`app/Shared/Traits/ApiResponse.php`**: Standardizes success and error JSON structures.
- **`routes/api.php`**: Entry point for all API routes, utilizing versioned groups.
- **`config/apiroute.php`**: Configuration for the `grazulex/laravel-apiroute` package.

## ❓ Unresolved Questions
- Should we introduce a `Repositories/` folder for complex queries, or keep them in Models/Services for now?
- How should we handle `Shared` services that grow too large (e.g., Image Processing)?
