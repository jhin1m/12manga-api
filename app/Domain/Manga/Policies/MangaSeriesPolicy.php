<?php

declare(strict_types=1);

namespace App\Domain\Manga\Policies;

use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MangaSeriesPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MangaSeries');
    }

    public function view(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('View:MangaSeries');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MangaSeries');
    }

    public function update(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('Update:MangaSeries');
    }

    public function delete(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('Delete:MangaSeries');
    }

    public function restore(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('Restore:MangaSeries');
    }

    public function forceDelete(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('ForceDelete:MangaSeries');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MangaSeries');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MangaSeries');
    }

    public function replicate(AuthUser $authUser, MangaSeries $mangaSeries): bool
    {
        return $authUser->can('Replicate:MangaSeries');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MangaSeries');
    }
}
