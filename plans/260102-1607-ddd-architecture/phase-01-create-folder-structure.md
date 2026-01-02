# Phase 01: Create Folder Structure

## Context Links
- [Main Plan](./plan.md)
- [README.md](../../README.md) - Architecture overview

## Overview

| Field | Value |
|-------|-------|
| Priority | P1 |
| Status | Pending |
| Effort | 15m |

Create the DDD Lite folder structure before moving any files.

## Key Insights

**Why DDD Lite?**
- Full DDD is overkill for this project size
- DDD Lite gives 80% of benefits with 20% of complexity
- Easy to upgrade to full DDD later if needed

**Why this structure?**
- `Domain/` - contains all business logic separated by domain
- `Shared/` - cross-cutting concerns used by multiple domains
- Each domain has `Models/`, `Services/`, `Actions/` folders

## Requirements

### Functional
- Create Domain folders: Manga, User, Community
- Create subdirectories: Models, Services, Actions
- Create Shared folder with Traits

### Non-Functional
- Follow PSR-4 autoloading conventions
- Maintain Laravel conventions where possible

## Architecture

```
app/
├── Domain/
│   ├── Manga/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Actions/
│   │
│   ├── User/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Actions/
│   │
│   └── Community/
│       ├── Models/
│       ├── Services/
│       └── Actions/
│
└── Shared/
    └── Traits/
```

## Related Code Files

**Create:**
- `app/Domain/Manga/Models/.gitkeep`
- `app/Domain/Manga/Services/.gitkeep`
- `app/Domain/Manga/Actions/.gitkeep`
- `app/Domain/User/Models/.gitkeep`
- `app/Domain/User/Services/.gitkeep`
- `app/Domain/User/Actions/.gitkeep`
- `app/Domain/Community/Models/.gitkeep`
- `app/Domain/Community/Services/.gitkeep`
- `app/Domain/Community/Actions/.gitkeep`
- `app/Shared/Traits/.gitkeep`

## Implementation Steps

### Step 1: Create Domain directories
```bash
mkdir -p app/Domain/Manga/{Models,Services,Actions}
mkdir -p app/Domain/User/{Models,Services,Actions}
mkdir -p app/Domain/Community/{Models,Services,Actions}
```

### Step 2: Create Shared directory
```bash
mkdir -p app/Shared/Traits
```

### Step 3: Add .gitkeep files (optional, keeps empty dirs in git)
```bash
touch app/Domain/Manga/Models/.gitkeep
touch app/Domain/Manga/Services/.gitkeep
touch app/Domain/Manga/Actions/.gitkeep
touch app/Domain/User/Models/.gitkeep
touch app/Domain/User/Services/.gitkeep
touch app/Domain/User/Actions/.gitkeep
touch app/Domain/Community/Models/.gitkeep
touch app/Domain/Community/Services/.gitkeep
touch app/Domain/Community/Actions/.gitkeep
touch app/Shared/Traits/.gitkeep
```

## Todo List

- [ ] Create Manga domain directories
- [ ] Create User domain directories
- [ ] Create Community domain directories
- [ ] Create Shared/Traits directory
- [ ] Add .gitkeep files
- [ ] Verify structure with `tree app/Domain`

## Success Criteria

- [ ] All directories exist
- [ ] Structure matches architecture diagram
- [ ] No errors from Laravel (artisan commands work)

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| None | Low | Simple directory creation |

## Security Considerations

N/A - no security impact

## Next Steps

Proceed to [Phase 02: Move and refactor Models](./phase-02-move-refactor-models.md)
