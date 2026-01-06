<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

/**
 * Form schema for User resource.
 *
 * Handles password hashing and avatar uploads.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Info')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null
                            )
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'
                            )
                            ->minLength(8)
                            ->confirmed()
                            ->revealable(),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->requiredWith('password')
                            ->dehydrated(false)
                            ->revealable(),
                    ])->columns(2),
                Section::make('Profile')
                    ->schema([
                        FileUpload::make('avatar')
                            ->image()
                            ->directory('avatars')
                            ->maxSize(1024)
                            ->imageEditor()
                            ->circleCropper(),
                        Textarea::make('bio')
                            ->rows(3)
                            ->maxLength(500),
                    ])->columns(2),
                Section::make('Roles & Permissions')
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
