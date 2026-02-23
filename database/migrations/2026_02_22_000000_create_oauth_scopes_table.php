<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the OAuth Scopes table for managing
     * dynamic scopes instead of hardcoded config values.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('group', 50)->nullable()->index(); // e.g., 'read', 'write', 'admin', 'mcp'
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('created_from')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['enabled', 'sort_order']);
        });

        // Seed default scopes from current config
        $this->seedDefaultScopes();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oauth_scopes');
    }

    /**
     * Seed default scopes from config.
     *
     * @return void
     */
    protected function seedDefaultScopes()
    {
        $scopes = [
            [
                'key' => 'mcp:use',
                'name' => 'MCP Access',
                'description' => 'Access MCP server capabilities and interact with your data via Model Context Protocol',
                'group' => 'mcp',
                'enabled' => true,
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'read',
                'name' => 'Read Access',
                'description' => 'Read your account data',
                'group' => 'basic',
                'enabled' => true,
                'is_default' => false,
                'sort_order' => 20,
            ],
            [
                'key' => 'write',
                'name' => 'Write Access',
                'description' => 'Create and modify your account data',
                'group' => 'basic',
                'enabled' => true,
                'is_default' => false,
                'sort_order' => 30,
            ],
            [
                'key' => 'admin',
                'name' => 'Admin Access',
                'description' => 'Full administrative access to your account',
                'group' => 'advanced',
                'enabled' => true,
                'is_default' => false,
                'sort_order' => 40,
            ],
        ];

        foreach ($scopes as $scope) {
            DB::table('oauth_scopes')->insert([
                'key' => $scope['key'],
                'name' => $scope['name'],
                'description' => $scope['description'],
                'group' => $scope['group'],
                'enabled' => $scope['enabled'],
                'is_default' => $scope['is_default'],
                'sort_order' => $scope['sort_order'],
                'created_from' => 'migration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
