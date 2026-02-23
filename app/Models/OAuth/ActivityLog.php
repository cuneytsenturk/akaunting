<?php

namespace App\Models\OAuth;

use App\Abstracts\Model;
use App\Models\Auth\User;
use App\Models\Common\Company;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    protected $table = 'oauth_activity_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'event_type',
        'resource_type',
        'resource_id',
        'client_name',
        'client_id',
        'token_id',
        'scopes',
        'ip_address',
        'user_agent',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the company that owns the activity log.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the user that owns the activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include recent activities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include specific event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEventType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope a query to only include activities for a specific client.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $clientId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to only include activities for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get formatted event type for display.
     *
     * @return string
     */
    public function getEventTypeLabelAttribute(): string
    {
        return trans('oauth.activity.events.' . str_replace('.', '_', $this->event_type));
    }

    /**
     * Get event icon based on event type.
     *
     * @return string
     */
    public function getEventIconAttribute(): string
    {
        return match ($this->event_type) {
            'token.created' => 'add_circle',
            'token.revoked' => 'remove_circle',
            'token.refreshed' => 'refresh',
            'client.created' => 'add_box',
            'client.updated' => 'edit',
            'client.deleted' => 'delete',
            'client.secret.regenerated' => 'vpn_key',
            'authorization.approved' => 'check_circle',
            'authorization.denied' => 'cancel',
            default => 'info',
        };
    }

    /**
     * Get event color based on event type.
     *
     * @return string
     */
    public function getEventColorAttribute(): string
    {
        return match ($this->event_type) {
            'token.created', 'client.created', 'authorization.approved' => 'green',
            'token.revoked', 'client.deleted', 'authorization.denied' => 'red',
            'token.refreshed', 'client.updated', 'client.secret.regenerated' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Log an OAuth activity.
     *
     * @param  array  $data
     * @return static
     */
    public static function logActivity(array $data): static
    {
        // Add request context if not provided
        if (!isset($data['ip_address']) && request()) {
            $data['ip_address'] = request()->ip();
        }

        if (!isset($data['user_agent']) && request()) {
            $data['user_agent'] = request()->userAgent();
        }

        return static::create($data);
    }
}
