<?php

namespace App\Models\OAuth;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\AuthCode as PassportAuthCode;

class AuthCode extends PassportAuthCode
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_auth_codes';

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
        static::creating(function ($authCode) {
            if (config('oauth.company_aware', true) && empty($authCode->company_id)) {
                // Priority 1: company selected by user on the consent screen
                if (session()->has('oauth.company_id')) {
                    $authCode->company_id = session('oauth.company_id');
                }

                // Priority 2: current web company context (e.g. URL-bound company)
                if (empty($authCode->company_id)) {
                    $authCode->company_id = company_id() ?: null;
                }

                // Priority 3: user's first enabled company (handles auto-approve with
                // multi-company users where neither session nor context is available)
                if (empty($authCode->company_id) && $authCode->user_id) {
                    if ($user = User::find($authCode->user_id)) {
                        if ($company = $user->companies()->enabled()->first()) {
                            $authCode->company_id = $company->id;
                        }
                    }
                }
            }

            // MCP REQUIRED: Set audience from OAuth session (RFC 8707)
            if (empty($authCode->audience) && session()->has('oauth.resource')) {
                $authCode->audience = session('oauth.resource');
            }

            // Set created_from and created_by
            if (empty($authCode->created_from)) {
                $authCode->created_from = request()->get('created_from') ?: 'oauth.web';
            }

            if (empty($authCode->created_by)) {
                $authCode->created_by = user_id();
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
     * Get the company that owns the auth code.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company', 'company_id');
    }

    /**
     * Get the user that created the auth code.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo('App\Models\Auth\User', 'created_by', 'id');
    }

    /**
     * Scope to get all auth codes without company filter.
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
}
