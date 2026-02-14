<?php

namespace App\Models\OAuth;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\PersonalAccessClient as PassportPersonalAccessClient;

class PersonalAccessClient extends PassportPersonalAccessClient
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_personal_access_clients';

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
        'client_id',
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
        'client_id' => 'integer',
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
        static::creating(function ($personalAccessClient) {
            if (config('oauth.company_aware', true) && empty($personalAccessClient->company_id)) {
                // Get company_id from related client
                if ($personalAccessClient->client) {
                    $personalAccessClient->company_id = $personalAccessClient->client->company_id;
                } else {
                    $personalAccessClient->company_id = company_id();
                }
            }

            // Set created_from and created_by
            if (empty($personalAccessClient->created_from)) {
                $personalAccessClient->created_from = request()->get('created_from') ?: 'oauth.web';
            }

            if (empty($personalAccessClient->created_by)) {
                $personalAccessClient->created_by = user_id();
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
     * Get the company that owns the personal access client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company', 'company_id');
    }

    /**
     * Get the user that created the personal access client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo('App\Models\Auth\User', 'created_by', 'id');
    }

    /**
     * Scope to get all personal access clients without company filter.
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
