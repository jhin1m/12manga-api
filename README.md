# Manga Reader API

A production-ready manga reading platform API built with Laravel 12, following Domain-Driven Design (DDD) Lite principles.

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## 🎯 Quick Start

### 1. Setup with Docker (Recommended)
```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose run --rm app php artisan storage:link
```

### 2. Run Tests
```bash
docker compose run --rm app ./vendor/bin/pest
```

## 📚 Documentation

Detailed documentation is available in the `docs/` directory:

- [**Project Overview & PDR**](./docs/project-overview-pdr.md) - Vision, goals, and requirements.
- [**Codebase Summary**](./docs/codebase-summary.md) - Directory structure and file conventions.
- [**Code Standards**](./docs/code-standards.md) - Architecture, naming, and patterns.
- [**System Architecture**](./docs/system-architecture.md) - Diagrams and technical design.
- [**API Documentation**](http://localhost:8080/docs/api) - Auto-generated Swagger UI (when running).

## 🏗️ Architecture at a Glance

This project uses **DDD Lite** to organize business logic into self-contained domains:

- **Manga Domain**: Series, chapters, genres, authors.
- **User Domain**: Profiles, follows, history.
- **Community Domain**: Comments, ratings.

## 🛣️ Core Endpoints (v1)

- `POST /api/v1/register` - User registration
- `POST /api/v1/login` - Get auth token
- `GET /api/v1/manga` - List/Search manga
- `GET /api/v1/manga/{slug}` - Manga details
- `GET /api/v1/user/profile` - Current user info

## 🛠️ Built With

- **Laravel 12** - Core framework
- **Laravel Sanctum** - Auth
- **Pest PHP** - Testing
- **Spatie Query Builder** - Filtering/Sorting
- **Scramble** - API Docs

---
**Status**: 🚧 In Development - Base architecture & DB schema implemented.
