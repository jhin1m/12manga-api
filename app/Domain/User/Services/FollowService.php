<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * FollowService - Handle follow/unfollow logic.
 */
class FollowService
{
    /**
     * Follow a manga.
     */
    public function follow(User $user, MangaSeries $manga): void
    {
        // Prevent duplicate follows
        if (! $user->followedManga()->where('manga_series_id', $manga->id)->exists()) {
            $user->followedManga()->attach($manga->id);
        }
    }

    /**
     * Unfollow a manga.
     */
    public function unfollow(User $user, MangaSeries $manga): void
    {
        $user->followedManga()->detach($manga->id);
    }

    /**
     * Check if user follows a manga.
     */
    public function isFollowing(User $user, MangaSeries $manga): bool
    {
        return $user->followedManga()->where('manga_series_id', $manga->id)->exists();
    }

    /**
     * Get user's followed manga.
     */
    public function getFollowedManga(User $user): Collection
    {
        return $user->followedManga()
            ->latest('follows.created_at')
            ->get();
    }
}
