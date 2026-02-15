<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MCP REQUIRED: Add audience (resource identifier) support to OAuth tokens.
     * This enables RFC 8707 Resource Indicators for OAuth 2.0 compliance.
     *
     * Reference: https://datatracker.ietf.org/doc/html/rfc8707
     *
     * @return void
     */
    public function up()
    {
        // Add audience to access tokens
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('audience')->nullable()->after('scopes')->index();
        });

        // Add audience to auth codes (for validation during token exchange)
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('audience')->nullable()->after('scopes')->index();
        });

        // Add audience to refresh tokens (inherited from access token)
        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->string('audience')->nullable()->after('access_token_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->dropColumn('audience');
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn('audience');
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
