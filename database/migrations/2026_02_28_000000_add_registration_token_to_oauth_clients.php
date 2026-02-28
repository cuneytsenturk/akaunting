<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `registration_token` to oauth_clients to support RFC 7591/7592
 * Dynamic Client Registration management endpoints (read, update, delete).
 *
 * The column stores a SHA-256 hash of the plain registration_access_token
 * that is returned once at registration time. This token acts as a bearer
 * credential for the /oauth/register/{id} management endpoints.
 *
 * Only DCR-created clients (created_from = 'oauth.dcr') will have this set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            // SHA-256 hex digest = 64 chars; nullable for manually-created clients
            $table->string('registration_token', 64)->nullable()->after('revoked');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('registration_token');
        });
    }
};
