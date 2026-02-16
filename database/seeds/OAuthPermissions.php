<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use Illuminate\Database\Seeder;

class OAuthPermissions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * OAuth-specific permissions for managing authorized applications.
     * These permissions are optional and complement existing auth-profile permissions.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // OAuth Client (Authorized Applications) Permissions
            [
                'name' => 'read-oauth-clients',
                'display_name' => 'Read OAuth Clients',
                'description' => 'View authorized OAuth applications',
            ],
            [
                'name' => 'update-oauth-clients',
                'display_name' => 'Update OAuth Clients',
                'description' => 'Revoke access to OAuth applications',
            ],
            [
                'name' => 'delete-oauth-clients',
                'display_name' => 'Delete OAuth Clients',
                'description' => 'Delete dynamically registered OAuth clients',
            ],

            // OAuth Token Management Permissions
            [
                'name' => 'read-oauth-tokens',
                'display_name' => 'Read OAuth Tokens',
                'description' => 'View personal access tokens',
            ],
            [
                'name' => 'create-oauth-tokens',
                'display_name' => 'Create OAuth Tokens',
                'description' => 'Create personal access tokens',
            ],
            [
                'name' => 'delete-oauth-tokens',
                'display_name' => 'Delete OAuth Tokens',
                'description' => 'Revoke personal access tokens',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                ]
            );
        }

        $this->command->info('OAuth permissions created successfully!');
    }
}
