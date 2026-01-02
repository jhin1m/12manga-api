<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;

/**
 * ToggleFollowAction - Toggle follow status for a manga.
 *
 * Why toggle instead of separate follow/unfollow?
 * - Single endpoint: POST /manga/{slug}/follow
 * - Returns new state to client
 * - Simpler API
 */
class ToggleFollowAction
{
    /**
     * Toggle follow status.
     *
     * @return array{following: bool, message: string}
     */
    public function __invoke(User $user, MangaSeries $manga): array
    {
        $isFollowing = $user->followedManga()
            ->where('manga_series_id', $manga->id)
            ->exists();

        if ($isFollowing) {
            $user->followedManga()->detach($manga->id);

            return [
                'following' => false,
                'message' => 'Unfollowed successfully',
            ];
        }

        $user->followedManga()->attach($manga->id);

        return [
            'following' => true,
            'message' => 'Followed successfully',
        ];
    }
}
