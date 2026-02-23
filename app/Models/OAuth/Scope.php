<?php

namespace App\Models\OAuth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scope extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_scopes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'group',
        'enabled',
        'is_default',
        'sort_order',
        'created_from',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_from and created_by
        static::creating(function ($scope) {
            if (empty($scope->created_from)) {
                $scope->created_from = 'oauth.web';
            }

            if (empty($scope->created_by) && function_exists('user_id')) {
                $scope->created_by = user_id();
            }

            // Auto-assign sort order if not provided
            if (empty($scope->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $scope->sort_order = $maxOrder + 10;
            }
        });

        // When marking as default, unmark other defaults
        static::saving(function ($scope) {
            if ($scope->is_default && $scope->isDirty('is_default')) {
                static::where('id', '!=', $scope->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Scope a query to only include enabled scopes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include default scope.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to order by sort order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query by group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get enabled scopes as array (key => description).
     *
     * @return array
     */
    public static function getEnabledScopes()
    {
        return static::enabled()
            ->ordered()
            ->pluck('description', 'key')
            ->toArray();
    }

    /**
     * Get the default scope key.
     *
     * @return string|null
     */
    public static function getDefaultScopeKey()
    {
        return static::default()->value('key');
    }

    /**
     * Get formatted display name with description.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ' - ' . $this->description;
    }
}
