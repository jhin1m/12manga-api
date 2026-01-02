<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * UserService - Business logic for user operations.
 */
class UserService
{
    /**
     * Update user profile.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        // Filter allowed fields
        $allowed = ['name', 'bio', 'avatar'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        $user->update($filtered);

        return $user->fresh();
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Find user by profile slug.
     */
    public function findBySlug(string $slug): ?User
    {
        return User::where('profile_slug', $slug)->first();
    }
}
