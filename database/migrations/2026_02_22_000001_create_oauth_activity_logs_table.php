<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type', 50)->index(); // token.created, token.revoked, client.created, etc.
            $table->string('resource_type', 50)->nullable(); // token, client, scope
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_id', 100)->nullable()->index();
            $table->string('token_id', 100)->nullable()->index();
            $table->json('scopes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Extra data (expires_at, grant_type, etc.)
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for common queries
            $table->index(['company_id', 'event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oauth_activity_logs');
    }
};
