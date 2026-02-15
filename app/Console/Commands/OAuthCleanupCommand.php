<?php

namespace App\Console\Commands;

use App\Models\OAuth\AccessToken;
use App\Models\OAuth\AuthCode;
use App\Models\OAuth\Client;
use App\Models\OAuth\RefreshToken;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OAuthCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:cleanup
                            {--clients : Cleanup expired dynamic clients}
                            {--tokens : Cleanup expired access tokens}
                            {--codes : Cleanup expired auth codes}
                            {--refresh-tokens : Cleanup expired refresh tokens}
                            {--all : Cleanup all expired OAuth data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OAuth clients, tokens, and authorization codes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $all = $this->option('all');

        if ($all || $this->option('clients')) {
            $this->cleanupExpiredClients();
        }

        if ($all || $this->option('tokens')) {
            $this->cleanupExpiredAccessTokens();
        }

        if ($all || $this->option('codes')) {
            $this->cleanupExpiredAuthCodes();
        }

        if ($all || $this->option('refresh-tokens')) {
            $this->cleanupExpiredRefreshTokens();
        }

        if (!$all && !$this->option('clients') && !$this->option('tokens') 
            && !$this->option('codes') && !$this->option('refresh-tokens')) {
            $this->error('Please specify what to cleanup: --clients, --tokens, --codes, --refresh-tokens, or --all');
            return 1;
        }

        $this->info('OAuth cleanup completed successfully.');
        return 0;
    }

    /**
     * Cleanup expired dynamically registered clients
     */
    protected function cleanupExpiredClients(): void
    {
        $expirationDays = config('oauth.dcr.client_expiration_days', 90);
        $cutoffDate = Carbon::now()->subDays($expirationDays);

        // Only cleanup dynamically registered clients (those with provider=null or provider='dcr')
        $expiredClients = Client::where(function ($query) {
                $query->whereNull('provider')
                      ->orWhere('provider', 'dcr');
            })
            ->where('updated_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($expiredClients as $client) {
            // Check if client has been used recently
            $recentlyUsed = AccessToken::where('client_id', $client->id)
                ->where('created_at', '>', $cutoffDate)
                ->exists();

            if (!$recentlyUsed) {
                // Delete associated tokens first
                AccessToken::where('client_id', $client->id)->delete();
                RefreshToken::where('client_id', $client->id)->delete();
                AuthCode::where('client_id', $client->id)->delete();
                
                // Delete the client
                $client->delete();
                $count++;
            }
        }

        $this->info("Cleaned up {$count} expired dynamic clients (inactive for {$expirationDays}+ days).");
    }

    /**
     * Cleanup expired access tokens
     */
    protected function cleanupExpiredAccessTokens(): void
    {
        $count = AccessToken::where('expires_at', '<', Carbon::now())
            ->delete();

        $this->info("Cleaned up {$count} expired access tokens.");
    }

    /**
     * Cleanup expired authorization codes
     */
    protected function cleanupExpiredAuthCodes(): void
    {
        // Auth codes typically expire in 10 minutes, cleanup those older than 1 day
        $cutoffDate = Carbon::now()->subDay();
        
        $count = AuthCode::where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Cleaned up {$count} old authorization codes.");
    }

    /**
     * Cleanup expired refresh tokens
     */
    protected function cleanupExpiredRefreshTokens(): void
    {
        $count = RefreshToken::where('expires_at', '<', Carbon::now())
            ->delete();

        $this->info("Cleaned up {$count} expired refresh tokens.");
    }
}
