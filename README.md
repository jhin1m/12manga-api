# Manga Reader API

A production-ready manga reading platform API built with Laravel 12, following Domain-Driven Design (DDD) principles. Based on [Laravel API Kit](https://github.com/Grazulex/laravel-api-kit) with extended features for manga management, reading tracking, and community interactions.

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## 🎯 Project Overview

A headless API for manga reading platforms supporting:
- **Manga browsing & reading** - Browse, search, and read manga with chapter management
- **User management** - Profile, reading history, bookmarks/follows
- **Community features** - Comments, ratings, and reviews
- **Admin panel** - Content moderation and user management
- **Multi-language support** - Alternative titles in multiple languages

## ✨ Features

### Core Features (From Laravel API Kit)
- **API-Only** - No Blade, Vite, or frontend assets
- **Token Authentication** - Laravel Sanctum for mobile/SPA auth
- **API Versioning** - URI-based versioning via [grazulex/laravel-apiroute](https://github.com/Grazulex/laravel-apiroute)
- **Query Building** - Advanced filtering, sorting via [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder)
- **Auto Documentation** - OpenAPI 3.1 via [dedoc/scramble](https://github.com/dedoc/scramble)
- **Modern Testing** - Pest PHP testing framework
- **Standardized Responses** - Consistent JSON response format

### Manga-Specific Features
- **Manga Management** - CRUD operations with cover images, genres, authors
- **Chapter System** - Chapter upload with page ordering and approval workflow
- **Reading Progress** - Track user reading history and last-read page
- **Follow System** - Users can follow manga series for updates
- **Comment System** - Nested comments on manga and chapters
- **Rating & Reviews** - 5-star rating system with reviews
- **Search & Filter** - Full-text search with genre, status, and type filters
- **Image Processing** - Cover upload with automatic resizing and optimization
- **Moderation** - Chapter approval system for content quality

## 🏗️ Architecture

This project follows **Domain-Driven Design (DDD) Lite** principles with three main domains:

```
app/
├── Domain/
│   ├── Manga/          # Core business - manga, chapters, genres, authors
│   ├── User/           # User profiles, follows, reading history
│   └── Community/      # Social features - comments, ratings
├── Http/               # API Layer - Controllers, Requests, Resources
└── Shared/             # Shared services - Image processing, caching
```

### Why DDD?

- **Clear boundaries** - Each domain is self-contained and focused
- **Easy to scale** - Add new domains without affecting existing code
- **Team-friendly** - Multiple developers can work on different domains
- **Microservice-ready** - Easy to extract domains into separate services later

## 📊 Database Schema

### Current Tables (Implemented)

**Users & Authentication**
- `users` - User accounts with profile fields (avatar, bio, slug)
- `personal_access_tokens` - Sanctum API tokens
- `password_reset_tokens` - Password reset functionality
- `sessions` - User session management

**Permissions** (via Spatie)
- `permissions` - Permission definitions
- `roles` - Role definitions
- `model_has_permissions` - User/Model permissions
- `model_has_roles` - User/Model roles
- `role_has_permissions` - Role permissions mapping

**Manga Domain**
- `manga_series` - Main manga information with cover, status, ratings
- `chapters` - Chapter information with approval workflow
- `chapter_images` - Chapter pages with ordering
- `genres` - Manga genres (Action, Romance, etc.)
- `authors` - Manga authors/artists

**Pivot Tables**
- `author_manga_series` - Many-to-many: authors ↔ manga
- `genre_manga_series` - Many-to-many: genres ↔ manga
- `follows` - User follows manga series

**System Tables**
- `cache`, `cache_locks` - Application caching
- `jobs`, `job_batches`, `failed_jobs` - Queue system

### Planned Tables (To Be Implemented)

**Community Features**
- `comments` - User comments on manga/chapters with nested replies
- `ratings` - User ratings (1-5 stars) for manga
- `reading_histories` - Track reading progress per chapter

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose (recommended)
- Or: PHP 8.3+, Composer 2.x, MySQL/PostgreSQL

### Installation with Docker

```bash
# Clone the repository
git clone <repository-url>
cd manga-reader-api

# Copy environment file
cp .env.example .env

# Build and start containers
docker compose build
docker compose up -d

# Install dependencies
docker compose run --rm app composer install

# Generate application key
docker compose run --rm app php artisan key:generate

# Run migrations
docker compose run --rm app php artisan migrate

# Link storage for file uploads
docker compose run --rm app php artisan storage:link

# (Optional) Seed sample data
docker compose run --rm app php artisan db:seed

# Run tests to verify installation
docker compose run --rm app ./vendor/bin/pest
```

### Installation without Docker

```bash
# Clone and install
git clone <repository-url>
cd manga-reader-api
composer install

# Configure
cp .env.example .env
php artisan key:generate

# Database setup
touch database/database.sqlite
php artisan migrate
php artisan storage:link

# Verify
./vendor/bin/pest
```

## 📚 API Documentation

Once running, access the auto-generated documentation:

- **Swagger UI**: [http://localhost:8080/docs/api](http://localhost:8080/docs/api)
- **OpenAPI JSON**: [http://localhost:8080/docs/api.json](http://localhost:8080/docs/api.json)

## 🔐 Authentication

Uses Laravel Sanctum for token-based authentication.

### Register
```bash
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Login
```bash
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response includes token:**
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

### Authenticated Requests
Include token in Authorization header:
```bash
Authorization: Bearer 1|abc123...
```

## 🛣️ API Endpoints

### Public Endpoints

#### Manga Browsing
```
GET    /api/v1/manga                    # List all manga (with filters)
GET    /api/v1/manga/popular            # Popular manga
GET    /api/v1/manga/latest             # Latest updates
GET    /api/v1/manga/search             # Search manga
GET    /api/v1/manga/{slug}             # Manga details
GET    /api/v1/manga/{slug}/chapters    # List chapters
GET    /api/v1/manga/{slug}/chapters/{chapter}  # Chapter pages

GET    /api/v1/genres                   # List genres
GET    /api/v1/authors                  # List authors
```

#### Query Examples
```bash
# Filter by genre
GET /api/v1/manga?filter[genre]=action

# Search by title
GET /api/v1/manga/search?q=one+piece

# Sort by popularity
GET /api/v1/manga?sort=-views_count

# Filter by status
GET /api/v1/manga?filter[status]=ongoing

# Combine filters
GET /api/v1/manga?filter[genre]=romance&filter[status]=completed&sort=-rating
```

### Protected Endpoints (Require Authentication)

#### User Profile & Preferences
```
GET    /api/v1/user/profile             # Get profile
PUT    /api/v1/user/profile             # Update profile
POST   /api/v1/user/profile/avatar      # Upload avatar

GET    /api/v1/user/follows             # My followed manga
POST   /api/v1/user/follows/{manga}     # Follow manga
DELETE /api/v1/user/follows/{manga}     # Unfollow manga

GET    /api/v1/user/history             # Reading history
POST   /api/v1/user/history/{chapter}   # Update progress
DELETE /api/v1/user/history/{manga}     # Clear history
```

#### Community Features
```
# Comments
GET    /api/v1/manga/{manga}/comments           # List comments
POST   /api/v1/manga/{manga}/comments           # Add comment
PUT    /api/v1/manga/{manga}/comments/{id}      # Edit comment
DELETE /api/v1/manga/{manga}/comments/{id}      # Delete comment
POST   /api/v1/manga/{manga}/comments/{id}/like # Like comment

# Ratings
POST   /api/v1/manga/{manga}/rate       # Rate manga (1-5)
GET    /api/v1/manga/{manga}/ratings    # View ratings
```

### Admin Endpoints (Require Admin Role)

```
# Dashboard
GET    /api/v1/admin/dashboard          # Stats overview

# Manga Management
POST   /api/v1/admin/manga              # Create manga
PUT    /api/v1/admin/manga/{id}         # Update manga
DELETE /api/v1/admin/manga/{id}         # Delete manga
POST   /api/v1/admin/manga/{id}/chapters      # Upload chapter
PUT    /api/v1/admin/manga/{id}/chapters/{id} # Edit chapter
DELETE /api/v1/admin/manga/{id}/chapters/{id} # Delete chapter

# User Management
GET    /api/v1/admin/users              # List users
POST   /api/v1/admin/users/{id}/ban     # Ban user
POST   /api/v1/admin/users/{id}/unban   # Unban user
```

## 📦 Domain Structure

### Manga Domain
```
app/Domain/Manga/
├── Models/
│   ├── MangaSeries.php      # Main manga entity
│   ├── Chapter.php          # Chapter with approval workflow
│   ├── ChapterImage.php     # Chapter pages
│   ├── Genre.php            # Genre taxonomy
│   └── Author.php           # Author/Artist
├── Services/
│   ├── MangaService.php     # Business logic for manga
│   ├── ChapterService.php   # Chapter management
│   └── SearchService.php    # Search functionality
└── Repositories/
    ├── MangaRepository.php  # Complex queries
    └── ChapterRepository.php
```

### User Domain
```
app/Domain/User/
├── Models/
│   ├── User.php             # Extended with profile fields
│   ├── Follow.php           # Manga follows
│   └── ReadingHistory.php   # Reading progress (planned)
└── Services/
    ├── UserService.php      # Profile management
    ├── FollowService.php    # Follow/unfollow logic
    └── ReadingHistoryService.php # Track progress
```

### Community Domain
```
app/Domain/Community/
├── Models/
│   ├── Comment.php          # Nested comments (planned)
│   └── Rating.php           # Ratings & reviews (planned)
└── Services/
    ├── CommentService.php   # Comment CRUD
    └── RatingService.php    # Rating calculations
```

### Shared Services
```
app/Shared/
├── Services/
│   ├── ImageService.php     # Image upload & processing
│   └── CacheService.php     # Cache management
└── Traits/
    ├── HasSlug.php          # Auto-generate slugs
    └── HasViewsCount.php    # Track view counts
```

## 🔧 Key Features Implementation

### 1. Image Upload & Processing
```php
// Automatic cover image optimization
POST /api/v1/admin/manga
Content-Type: multipart/form-data

{
  "title": "One Piece",
  "cover_image": <file>,  # Auto-resized to 500x700
  ...
}
```

### 2. Reading Progress Tracking
```php
// Automatically track last-read page
POST /api/v1/user/history/{chapter}
{
  "last_page": 15,
  "completed": false
}

// Continue reading from last position
GET /api/v1/user/continue-reading
```

### 3. Chapter Approval Workflow
```php
// Chapters require approval before public display
$chapter->is_approved = false; // Default

// Admin approves
PUT /api/v1/admin/chapters/{id}
{
  "is_approved": true
}
```

### 4. Nested Comments System
```php
// Reply to a comment
POST /api/v1/manga/{manga}/comments
{
  "content": "Great manga!",
  "parent_id": 123  # Optional - for replies
}
```

### 5. Advanced Search
```php
// Full-text search with filters
GET /api/v1/manga/search?q=naruto
  &filter[genre]=action
  &filter[status]=completed
  &sort=-rating
  &include=authors,genres
```

## 🧪 Testing

### Run Tests
```bash
# All tests
docker compose run --rm app ./vendor/bin/pest

# Specific domain
docker compose run --rm app ./vendor/bin/pest tests/Feature/Domain/Manga

# With coverage
docker compose run --rm app ./vendor/bin/pest --coverage
```

### Test Structure
```
tests/
├── Feature/
│   ├── Domain/
│   │   ├── Manga/
│   │   │   ├── MangaTest.php
│   │   │   └── ChapterTest.php
│   │   ├── User/
│   │   │   └── FollowTest.php
│   │   └── Community/
│   │       └── CommentTest.php
│   └── Api/
│       └── V1/
│           └── AuthTest.php
└── Unit/
    └── Services/
        ├── MangaServiceTest.php
        └── ImageServiceTest.php
```

## 🚀 Deployment

### Production Checklist

**Environment**
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure production database (MySQL/PostgreSQL)
- [ ] Set proper `APP_URL`
- [ ] Configure `SANCTUM_STATEFUL_DOMAINS`
- [ ] Set up file storage (S3/DO Spaces recommended)

**Performance**
- [ ] Enable opcache
- [ ] Configure Redis for caching
- [ ] Set up queue workers
- [ ] Configure CDN for images
- [ ] Enable GZIP compression

**Security**
- [ ] Enable HTTPS
- [ ] Review CORS settings
- [ ] Set proper rate limits
- [ ] Configure backup strategy
- [ ] Set up monitoring & logging

**Database**
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Link storage: `php artisan storage:link`
- [ ] Seed initial data: `php artisan db:seed --class=GenreSeeder`

## 📈 Roadmap

### Phase 1: Foundation (Current)
- [x] Database schema design
- [x] User authentication
- [x] Basic CRUD for manga
- [ ] Image upload system
- [ ] Chapter management

### Phase 2: Core Features
- [ ] Reading progress tracking
- [ ] Follow system
- [ ] Search & filters
- [ ] Admin panel basics

### Phase 3: Community
- [ ] Comment system
- [ ] Rating & reviews
- [ ] User profiles
- [ ] Notifications

### Phase 4: Advanced
- [ ] Recommendation engine
- [ ] Reading lists
- [ ] Social features (shares, likes)
- [ ] Mobile app API optimization

### Phase 5: Scale
- [ ] Caching optimization
- [ ] CDN integration
- [ ] Microservices extraction
- [ ] Real-time features (WebSocket)

## 🛠️ Development

### Code Style
```bash
# Format code
docker compose run --rm app ./vendor/bin/pint

# Check without fixing
docker compose run --rm app ./vendor/bin/pint --test
```

### Useful Commands
```bash
# Create new migration
php artisan make:migration create_comments_table

# Create model with factory
php artisan make:model Comment -mf

# Create controller
php artisan make:controller Api/V1/CommentController

# Create request
php artisan make:request StoreCommentRequest

# Create resource
php artisan make:resource CommentResource

# Clear caches
php artisan optimize:clear

# List routes
php artisan route:list
```

## 📝 Environment Variables

Key `.env` configurations:

```env
# Application
APP_NAME="Manga Reader API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=manga_reader
DB_USERNAME=laravel
DB_PASSWORD=secret

# File Storage
FILESYSTEM_DISK=public
# For production: use s3, do, etc.

# Image Processing
MANGA_COVER_WIDTH=500
MANGA_COVER_HEIGHT=700
CHAPTER_IMAGE_MAX_WIDTH=1200

# Rate Limiting
API_RATE_LIMIT=60
AUTH_RATE_LIMIT=5

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Follow DDD structure - place files in correct domain
4. Write tests for new features
5. Ensure code passes `./vendor/bin/pint`
6. Commit changes (`git commit -m 'Add amazing feature'`)
7. Push to branch (`git push origin feature/amazing-feature`)
8. Open Pull Request

### Contribution Guidelines

- Follow DDD principles - keep domains separated
- Write tests for all new features
- Update API documentation
- Follow PSR-12 coding standards
- Keep controllers thin - logic in services
- Use type hints and return types
- Add PHPDoc blocks for public methods

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

### Built With
- [Laravel](https://laravel.com) - The PHP Framework
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API Authentication
- [Laravel API Kit](https://github.com/Grazulex/laravel-api-kit) - Base starter kit
- [grazulex/laravel-apiroute](https://github.com/Grazulex/laravel-apiroute) - API Versioning
- [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) - Advanced Queries
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission) - Role & Permission
- [dedoc/scramble](https://github.com/dedoc/scramble) - API Documentation
- [Pest PHP](https://pestphp.com) - Testing Framework

### Inspiration
- [MangaDex API](https://api.mangadex.org/docs/) - API design reference
- [Anilist API](https://anilist.gitbook.io/anilist-apiv2-docs/) - GraphQL patterns
- Various manga reader platforms

## 📞 Support

- **Documentation**: [Link to Wiki/Docs]
- **Issues**: [GitHub Issues](issues)
- **Discussions**: [GitHub Discussions](discussions)
- **Discord**: [Community Server] (if applicable)

---

**Status**: 🚧 In Development - Database schema implemented, API endpoints in progress

**Last Updated**: January 2026