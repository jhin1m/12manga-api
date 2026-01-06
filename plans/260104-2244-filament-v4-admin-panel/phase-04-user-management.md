# Phase 04: User Management Resource

## Context Links
- Parent: [plan.md](./plan.md)
- Depends on: [Phase 02](./phase-02-shield-authentication.md)
- Model: `app/Domain/User/Models/User.php`

## Overview
- **Priority**: P2
- **Effort**: 1h
- **Status**: Pending
- **Description**: Create Filament resource for User management with role assignment

## Key Insights
- User model already has HasRoles trait from spatie
- Shield provides RoleResource, need UserResource
- Must handle password field carefully (hash, optional on edit)
- Avoid exposing sensitive fields

## Requirements

### Functional
- List all users with role info
- Create new users with role assignment
- Edit user profile and roles
- Soft actions: ban/unban (if banned_until field exists)

### Non-Functional
- Password hashing handled properly
- No exposure of password/tokens in responses
- Proper validation for unique email

## Architecture

```
app/Filament/Resources/
└── UserResource.php
└── UserResource/
    └── Pages/
        ├── ListUsers.php
        ├── CreateUser.php
        └── EditUser.php
```

## Related Code Files

| Action | File | Description |
|--------|------|-------------|
| CREATE | `app/Filament/Resources/UserResource.php` | User management resource |
| CREATE | `app/Filament/Resources/UserResource/Pages/*.php` | Resource pages |

## Implementation Steps

### Step 1: Generate UserResource
```bash
php artisan make:filament-resource User --generate
```

### Step 2: Configure UserResource
Edit `app/Filament/Resources/UserResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\User\Models\User;
use App\Filament\Resources\UserResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) =>
                            filled($state) ? Hash::make($state) : null
                        )
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation): bool =>
                            $operation === 'create'
                        )
                        ->minLength(8)
                        ->confirmed()
                        ->revealable(),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->password()
                        ->requiredWith('password')
                        ->dehydrated(false)
                        ->revealable(),
                ])->columns(2),
            Forms\Components\Section::make('Profile')
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->image()
                        ->directory('avatars')
                        ->maxSize(1024)
                        ->imageEditor()
                        ->circleCropper(),
                    Forms\Components\Textarea::make('bio')
                        ->rows(3)
                        ->maxLength(500),
                ])->columns(2),
            Forms\Components\Section::make('Roles & Permissions')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) =>
                        'https://ui-avatars.com/api/?name='.urlencode($record->name)
                    ),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->label('Verified')
                    ->placeholder('Not verified')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        // Prevent deleting self
                        if ($record->id === auth()->id()) {
                            throw new \Exception('Cannot delete yourself');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
```

### Step 3: Add ViewUser Page
```bash
php artisan make:filament-page ViewUser --resource=UserResource --type=ViewRecord
```

### Step 4: Generate Permissions
```bash
php artisan shield:generate --resource=UserResource
```

## Todo List
- [ ] Generate UserResource with `make:filament-resource`
- [ ] Configure form with password handling
- [ ] Configure table with role badges
- [ ] Add ViewUser page
- [ ] Implement delete protection (no self-delete)
- [ ] Generate Shield permissions
- [ ] Test user CRUD operations
- [ ] Test role assignment

## Success Criteria
- [ ] UserResource visible in sidebar under "User Management"
- [ ] Can create new users with roles
- [ ] Password properly hashed on create/update
- [ ] Cannot delete own account
- [ ] Role changes take effect immediately

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Password exposure | High | Use dehydrateStateUsing with Hash |
| Self-deletion | Medium | Add before hook on delete action |
| Role escalation | High | Shield permissions restrict access |

## Security Considerations
- Password never returned in responses
- Password hashed before storage
- Users cannot delete themselves
- Role assignment controlled by Shield permissions
- Avatar uploads restricted to images

## Next Steps
→ Phase 05: Dashboard Widgets
