<?php

namespace App\Console\Commands;

use App\Models\OAuth\AccessToken;
use App\Models\OAuth\AuthCode;
use App\Models\OAuth\RefreshToken;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * oauth:purge — Permanently remove revoked and/or expired OAuth records.
 *
 * Mirrors Passport's `passport:purge` but is Akaunting-aware:
 *   - Bypasses the company global scope to process all companies at once.
 *   - Uses forceDelete() to permanently remove soft-deleted records.
 *   - Cascades: purging an access token also purges its refresh tokens.
 *
 * Usage:
 *   php artisan oauth:purge                  # revoked + expired (default)
 *   php artisan oauth:purge --revoked        # only revoked tokens
 *   php artisan oauth:purge --expired        # only expired tokens
 *   php artisan oauth:purge --hours=6        # only expired 6+ hours ago
 *   php artisan oauth:purge --force          # skip confirmation prompt
 */
class OAuthPurge extends Command
{
    protected $signature = 'oauth:purge
                            {--revoked  : Only purge revoked tokens (ignore expiry)}
                            {--expired  : Only purge expired tokens (ignore revoked status)}
                            {--hours=0  : For expired tokens: only purge those expired N+ hours ago}
                            {--force    : Skip the confirmation prompt}';

    protected $description = 'Permanently remove revoked and/or expired OAuth tokens, refresh tokens, and auth codes';

    public function handle(): int
    {
        $revokedOnly = $this->option('revoked');
        $expiredOnly = $this->option('expired');
        $hours       = (int) $this->option('hours');

        // Default (no flag): purge both revoked and expired — same as passport:purge
        $purgeRevoked = !$expiredOnly;  // true unless --expired only
        $purgeExpired = !$revokedOnly;  // true unless --revoked only

        if (!$this->option('force')) {
            $what = match (true) {
                $purgeRevoked && $purgeExpired => 'revoked and expired',
                $purgeRevoked                  => 'revoked',
                $purgeExpired                  => 'expired',
            };

            $hoursNote = ($purgeExpired && $hours > 0) ? " (expired {$hours}+ hours ago)" : '';

            if (!$this->confirm("This will permanently delete all {$what} OAuth tokens{$hoursNote}. Continue?")) {
                $this->info('Aborted.');
                return 0;
            }
        }

        $this->newLine();

        // ------------------------------------------------------------------
        // 1. Access Tokens
        // ------------------------------------------------------------------
        $accessQuery = AccessToken::allCompanies()->withTrashed();

        $accessQuery->where(function ($q) use ($purgeRevoked, $purgeExpired, $hours) {
            $q->where(function ($inner) {
                // Already soft-deleted records are always eligible for force-delete
                $inner->whereNotNull('deleted_at');
            });

            if ($purgeRevoked) {
                $q->orWhere('revoked', true);
            }

            if ($purgeExpired) {
                $cutoff = $hours > 0
                    ? Carbon::now()->subHours($hours)
                    : Carbon::now();

                $q->orWhere('expires_at', '<', $cutoff);
            }
        });

        // Collect IDs before deleting so we can cascade to refresh tokens
        $tokenIds = (clone $accessQuery)->pluck('id');

        // Cascade: purge all refresh tokens belonging to these access tokens
        $refreshCount = 0;
        if ($tokenIds->isNotEmpty()) {
            $refreshCount = RefreshToken::allCompanies()
                ->withTrashed()
                ->whereIn('access_token_id', $tokenIds)
                ->forceDelete();
        }

        $accessCount = $accessQuery->forceDelete();

        $this->components->twoColumnDetail(
            '<fg=green>Access tokens purged</>',
            "<fg=yellow>{$accessCount}</>"
        );
        $this->components->twoColumnDetail(
            '<fg=green>Refresh tokens purged</>',
            "<fg=yellow>{$refreshCount}</>"
        );

        // ------------------------------------------------------------------
        // 2. Orphaned Refresh Tokens
        //    (refresh tokens whose access token no longer exists)
        // ------------------------------------------------------------------
        $orphanedRefreshCount = RefreshToken::allCompanies()
            ->withTrashed()
            ->where(function ($q) use ($purgeRevoked, $purgeExpired, $hours) {
                $q->whereNotNull('deleted_at');

                if ($purgeRevoked) {
                    $q->orWhere('revoked', true);
                }

                if ($purgeExpired) {
                    $cutoff = $hours > 0
                        ? Carbon::now()->subHours($hours)
                        : Carbon::now();

                    $q->orWhere('expires_at', '<', $cutoff);
                }
            })
            ->whereDoesntHave('accessToken', fn ($q) => $q->withTrashed())
            ->forceDelete();

        if ($orphanedRefreshCount > 0) {
            $this->components->twoColumnDetail(
                '<fg=green>Orphaned refresh tokens purged</>',
                "<fg=yellow>{$orphanedRefreshCount}</>"
            );
        }

        // ------------------------------------------------------------------
        // 3. Authorization Codes
        //    Auth codes expire in ~10 minutes; always purge expired ones.
        //    Revoked codes are also safe to remove permanently.
        // ------------------------------------------------------------------
        $authCodeQuery = AuthCode::allCompanies()->withTrashed()
            ->where(function ($q) use ($purgeRevoked, $purgeExpired, $hours) {
                $q->whereNotNull('deleted_at');

                if ($purgeRevoked) {
                    $q->orWhere('revoked', true);
                }

                if ($purgeExpired) {
                    $cutoff = $hours > 0
                        ? Carbon::now()->subHours($hours)
                        : Carbon::now();

                    $q->orWhere('expires_at', '<', $cutoff);
                }
            });

        $authCodeCount = $authCodeQuery->forceDelete();

        $this->components->twoColumnDetail(
            '<fg=green>Authorization codes purged</>',
            "<fg=yellow>{$authCodeCount}</>"
        );

        $this->newLine();
        $this->components->info('OAuth purge completed.');

        return 0;
    }
}
