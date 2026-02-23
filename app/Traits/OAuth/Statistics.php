<?php

namespace App\Traits\OAuth;

use App\Models\OAuth\AccessToken;
use App\Models\OAuth\ActivityLog;
use App\Models\OAuth\Client;
use Illuminate\Support\Facades\DB;

trait Statistics
{
    /**
     * Get total active tokens count.
     *
     * @return int
     */
    public function getTotalActiveTokens(): int
    {
        return AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->count();
    }

    /**
     * Get total clients count.
     *
     * @return int
     */
    public function getTotalClients(): int
    {
        return Client::where('company_id', company_id())->count();
    }

    /**
     * Get recent activity count.
     *
     * @param  int  $days
     * @return int
     */
    public function getRecentActivityCount(int $days = 7): int
    {
        return ActivityLog::where('company_id', company_id())
            ->recent($days)
            ->count();
    }

    /**
     * Get tokens created in the last X days.
     *
     * @param  int  $days
     * @return int
     */
    public function getTokensCreatedRecently(int $days = 30): int
    {
        return ActivityLog::where('company_id', company_id())
            ->eventType('token.created')
            ->recent($days)
            ->count();
    }

    /**
     * Get tokens revoked in the last X days.
     *
     * @param  int  $days
     * @return int
     */
    public function getTokensRevokedRecently(int $days = 30): int
    {
        return ActivityLog::where('company_id', company_id())
            ->eventType('token.revoked')
            ->recent($days)
            ->count();
    }

    /**
     * Get most active clients.
     *
     * @param  int  $limit
     * @param  int  $days
     * @return \Illuminate\Support\Collection
     */
    public function getMostActiveClients(int $limit = 5, int $days = 30)
    {
        return ActivityLog::where('company_id', company_id())
            ->recent($days)
            ->whereNotNull('client_id')
            ->select('client_id', 'client_name', DB::raw('count(*) as activity_count'))
            ->groupBy('client_id', 'client_name')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity breakdown by event type.
     *
     * @param  int  $days
     * @return \Illuminate\Support\Collection
     */
    public function getActivityBreakdown(int $days = 30)
    {
        return ActivityLog::where('company_id', company_id())
            ->recent($days)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'event_type' => $item->event_type,
                    'count' => $item->count,
                    'label' => trans('oauth.activity.events.' . str_replace('.', '_', $item->event_type)),
                ];
            });
    }

    /**
     * Get daily activity trend for the last X days.
     *
     * @param  int  $days
     * @return \Illuminate\Support\Collection
     */
    public function getDailyActivityTrend(int $days = 30)
    {
        $activities = ActivityLog::where('company_id', company_id())
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates with 0 count
        $result = collect();
        $startDate = now()->subDays($days)->startOfDay();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $activity = $activities->firstWhere('date', $dateStr);
            
            $result->push([
                'date' => $dateStr,
                'formatted_date' => $date->format('M d'),
                'count' => $activity ? $activity->count : 0,
            ]);
        }

        return $result;
    }

    /**
     * Get token expiration statistics.
     *
     * @return array
     */
    public function getTokenExpirationStats(): array
    {
        $total = AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->count();

        $expiringSoon = AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->count();

        $expired = AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->where('expires_at', '<=', now())
            ->count();

        $active = AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
            'percent_active' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get client type distribution.
     *
     * @return array
     */
    public function getClientTypeDistribution(): array
    {
        $total = Client::where('company_id', company_id())->count();
        
        $confidential = Client::where('company_id', company_id())
            ->whereNotNull('secret')
            ->count();

        $public = $total - $confidential;

        $personal = Client::where('company_id', company_id())
            ->where('personal_access_client', true)
            ->count();

        $password = Client::where('company_id', company_id())
            ->where('password_client', true)
            ->count();

        return [
            'total' => $total,
            'confidential' => $confidential,
            'public' => $public,
            'personal_access' => $personal,
            'password_grant' => $password,
        ];
    }

    /**
     * Get top users by token count.
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getTopUsersByTokens(int $limit = 5)
    {
        return AccessToken::where('company_id', company_id())
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->select('user_id', DB::raw('count(*) as token_count'))
            ->groupBy('user_id')
            ->orderByDesc('token_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = \App\Models\Auth\User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'name' => $user ? $user->name : trans('general.unknown'),
                    'email' => $user ? $user->email : '-',
                    'token_count' => $item->token_count,
                ];
            });
    }
}
