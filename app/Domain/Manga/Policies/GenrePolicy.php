<?php

declare(strict_types=1);

namespace App\Domain\Manga\Policies;

use App\Domain\Manga\Models\Genre;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class GenrePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Genre');
    }

    public function view(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('View:Genre');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Genre');
    }

    public function update(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('Update:Genre');
    }

    public function delete(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('Delete:Genre');
    }

    public function restore(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('Restore:Genre');
    }

    public function forceDelete(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('ForceDelete:Genre');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Genre');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Genre');
    }

    public function replicate(AuthUser $authUser, Genre $genre): bool
    {
        return $authUser->can('Replicate:Genre');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Genre');
    }
}
