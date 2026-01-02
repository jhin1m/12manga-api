<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;

/**
 * UpdateProfileAction - Updates user profile information.
 */
class UpdateProfileAction
{
    /**
     * Allowed profile fields.
     *
     * @var array<int, string>
     */
    private const ALLOWED_FIELDS = ['name', 'bio', 'avatar'];

    /**
     * Update user profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $user, array $data): User
    {
        // Filter to allowed fields only (security)
        $filtered = array_intersect_key(
            $data,
            array_flip(self::ALLOWED_FIELDS)
        );

        $user->update($filtered);

        return $user->fresh();
    }
}
