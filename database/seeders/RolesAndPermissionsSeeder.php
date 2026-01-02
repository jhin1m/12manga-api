<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds roles and permissions for the manga reader API.
 *
 * Why this seeder?
 * - Sets up RBAC (Role-Based Access Control) using Spatie Permission
 * - Defines what each role can do in the system
 *
 * Role hierarchy (highest to lowest):
 * - admin: Full system access
 * - group_member: Can upload and manage chapters
 * - user: Regular registered user
 * - guest: Unauthenticated visitor (limited access)
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions to avoid stale data
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        // Why these specific permissions?
        $permissions = [
            // Manga management - for admins and moderators
            'manage_manga' => 'Create, edit, delete manga series',

            // Chapter operations - for scanlation groups
            'upload_chapter' => 'Upload new chapters',
            'approve_chapter' => 'Approve/reject pending chapters',

            // Content moderation - for moderators
            'moderate_content' => 'Edit/delete inappropriate content',

            // User administration - for admins only
            'manage_users' => 'Ban, edit, delete users',
        ];

        foreach ($permissions as $name => $description) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions
        // Why these roles?
        // - admin: System administrators with full access
        // - group_member: Scanlation group members who upload chapters
        // - user: Regular users who read manga and follow series
        // - guest: For future permission checks on unauthenticated users

        // Admin: Has all permissions
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        // Group member: Can upload and manage their chapters
        $groupMember = Role::create(['name' => 'group_member', 'guard_name' => 'web']);
        $groupMember->givePermissionTo([
            'upload_chapter',
        ]);

        // User: Regular registered user (no special permissions for now)
        // Future: May add permissions like 'rate_manga', 'comment'
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        // Guest: For future permission system
        // Useful for checking access without being logged in
        Role::create(['name' => 'guest', 'guard_name' => 'web']);
    }
}
