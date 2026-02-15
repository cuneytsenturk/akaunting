<?php

namespace App\Models\OAuth;

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
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the model should use company scope.
     *
     * @var bool
     */
    protected $companyAware = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'expires_at',
        'created_from',
        'created_by',
    ];

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
     * Boot the model.
     */
    public static function boot(): void
    {
        parent::boot();

        // Automatically set company_id when creating
        static::creating(function ($token) {
            if (config('oauth.company_aware', true) && empty($token->company_id)) {
                // Priority 1: Get from AuthCode (authorization code flow)
                // When exchanging authorization code for token, inherit company_id from auth code
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
                }
                
                // Priority 2: Get from OAuth session (during authorization - implicit/password grant)
                if (empty($token->company_id) && session()->has('oauth.company_id')) {
                    $token->company_id = session('oauth.company_id');
                }
                
                // Priority 3: Get from current session (personal access tokens, API calls)
                if (empty($token->company_id)) {
                    $token->company_id = company_id();
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
