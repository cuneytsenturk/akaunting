<?php

namespace App\Models\OAuth;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\Token as PassportToken;

class AccessToken extends PassportToken
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_tokens';

    /**
     * Indicates if the model should use company scope.
     *
     * @var bool
     */
    protected $companyAware = true;

    /**
     * The attributes that aren't mass assignable.
     * Using empty guarded to match Passport's behavior.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'client_id' => 'integer',
        'scopes' => 'array',
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'created_by' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', // Must be explicitly fillable: $guarded=[] + non-empty $fillable still strips unlisted keys
        'user_id',
        'client_id',
        'company_id',
        'name',
        'scopes',
        'audience', // MCP REQUIRED: RFC 8707 Resource Identifier
        'revoked',
        'created_from',
        'created_by',
        'expires_at',
    ];

    /**
     * Boot the model.
     */
    public static function boot(): void
    {
        parent::boot();

        // Automatically set company_id when creating
        static::creating(function ($token) {
            if (config('oauth.company_aware', true) && empty($token->company_id)) {
                // Priority 1: Authorization code flow — inherit company_id from the auth code.
                // The auth code was created in the user's browser session and has company_id set.
                if ($token->user_id && $token->client_id) {
                    $authCode = \App\Models\OAuth\AuthCode::withoutGlobalScope('company')
                        ->where('user_id', $token->user_id)
                        ->where('client_id', $token->client_id)
                        ->where('revoked', false)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($authCode && $authCode->company_id) {
                        $token->company_id = $authCode->company_id;
                    }

                    // MCP REQUIRED: Inherit audience from AuthCode (RFC 8707)
                    if ($authCode && $authCode->audience && empty($token->audience)) {
                        $token->audience = $authCode->audience;
                    }
                }

                // Priority 2: Refresh token flow — inherit company_id from the most recent
                // access token for the same user+client. When ChatGPT exchanges a refresh
                // token, there is no auth code and no session, but the previous access token
                // already has the correct company_id.
                if (empty($token->company_id) && $token->user_id && $token->client_id) {
                    $previous = static::withoutGlobalScope('company')
                        ->where('user_id', $token->user_id)
                        ->where('client_id', $token->client_id)
                        ->whereNotNull('company_id')
                        ->where('company_id', '>', 0)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($previous) {
                        $token->company_id = $previous->company_id;
                    }
                }

                // Priority 3: OAuth session (set during Authorize::approve()).
                // Only available when the token request originates from the user's browser.
                if (empty($token->company_id) && session()->has('oauth.company_id')) {
                    $token->company_id = session('oauth.company_id');
                }

                // Priority 4: Look up the user directly and take their first enabled company.
                // This handles any remaining case (personal access tokens, auto-approve with
                // no session, or any grant where the above fallbacks find nothing).
                if (empty($token->company_id) && $token->user_id) {
                    if ($user = User::find($token->user_id)) {
                        if ($company = $user->companies()->enabled()->first()) {
                            $token->company_id = $company->id;
                        }
                    }
                }
            }

            // MCP REQUIRED: Set audience from session if not already set
            if (empty($token->audience)) {
                if (session()->has('oauth.resource')) {
                    $token->audience = session('oauth.resource');
                } elseif (request()->has('resource')) {
                    $token->audience = request()->input('resource');
                } else {
                    // Default to application URL
                    $token->audience = url('/');
                }
            }

            // Set created_from and created_by
            if (empty($token->created_from)) {
                $token->created_from = request()->get('created_from') ?: 'oauth.api';
            }

            if (empty($token->created_by)) {
                $token->created_by = user_id();
            }
        });

        // Apply company scope if enabled
        if (config('oauth.company_aware', true)) {
            static::addGlobalScope('company', function ($builder) {
                if ($companyId = company_id()) {
                    $builder->where('company_id', $companyId);
                }
            });
        }
    }

    /**
     * Get the company that owns the token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company', 'company_id');
    }

    /**
     * Get the user that created the token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo('App\Models\Auth\User', 'created_by', 'id');
    }

    /**
     * Scope to get all tokens without company filter.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllCompanies($query)
    {
        return $query->withoutGlobalScope('company');
    }

    /**
     * Scope to filter by company ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompanyId($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Determine if the token is a transient token.
     *
     * @return bool
     */
    public function transient()
    {
        return false;
    }
}
