<?php

namespace App\Services\OAuth;

use App\Models\Auth\Permission;
use App\Models\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ScopeMapper — Dynamic OAuth scope ↔ permission conversion.
 *
 * Convention:
 *   Permission format : [action]-[category]-[feature]   (e.g. read-sales-invoices)
 *   Scope format      : [category]:[action_group]        (e.g. sales:read)
 *
 * Action groups:
 *   read           → :read
 *   create/update  → :write
 *   delete         → :delete
 *
 * Special scopes (mcp:use, etc.) are manually defined and never auto-generated.
 * New modules that follow the permission naming convention automatically
 * receive their own scopes without any changes here.
 */
class ScopeMapper
{
    /**
     * Maps raw permission actions to OAuth action-group labels.
     */
    const ACTION_MAP = [
        'read'   => 'read',
        'create' => 'write',
        'update' => 'write',
        'delete' => 'delete',
    ];

    /**
     * Permission resources ([category]-[feature] after stripping the action prefix)
     * that must NOT be exposed as OAuth API scopes.
     *
     * These are UI-only, system-level, or internal areas that have no meaning
     * outside the Akaunting web application.
     */
    const EXCLUDED = [
        // System / admin
        'admin-panel',
        'api',
        'install-updates',
        'notifications',

        // Auth / user management
        'auth-users',
        'auth-profile',

        // Settings (admin UI)
        'settings-categories',
        'settings-company',
        'settings-currencies',
        'settings-defaults',
        'settings-email',
        'settings-email-templates',
        'settings-invoice',
        'settings-localisation',
        'settings-modules',
        'settings-oauth',
        'settings-oauth-activity',
        'settings-oauth-dashboard',
        'settings-oauth-scopes',
        'settings-schedule',
        'settings-taxes',

        // Client portal (web UI only)
        'client-portal',
        'portal-invoices',
        'portal-payments',
        'portal-profile',

        // Dashboard widgets (web UI only)
        'widgets-account-balance',
        'widgets-bank-feeds',
        'widgets-cash-flow',
        'widgets-currencies',
        'widgets-expenses-by-category',
        'widgets-payables',
        'widgets-profit-loss',
        'widgets-receivables',

        // App store / module manager (web UI only)
        'modules-api-key',
        'modules-home',
        'modules-item',
        'modules-my',
        'modules-tiles',

        // Common sub-features that are web-only
        'common-dashboards',
        'common-search',
        'common-uploads',
        'common-companies',
        'common-reports',
        'common-widgets',
        'common-import',
    ];

    /**
     * When a specific [category-feature] matches a key here, the scope
     * category is replaced with the alias value.
     *
     * Primarily used to give common-items its own "items" namespace so it
     * doesn't pollute the generic "common" scope category.
     */
    const CATEGORY_ALIASES = [
        'common-items' => 'items',
    ];

    /**
     * Scopes that are manually managed, never derived from permissions.
     * toScope() will never produce these; they are always registered separately.
     */
    const MANUAL_SCOPES = [
        'mcp:use',
    ];

    // -------------------------------------------------------------------------
    // Permission → Scope
    // -------------------------------------------------------------------------

    /**
     * Convert a single permission name to its OAuth scope key.
     * Returns null when the permission should not be exposed as a scope.
     *
     * Examples:
     *   'read-sales-invoices'     → 'sales:read'
     *   'create-sales-invoices'   → 'sales:write'
     *   'update-banking-accounts' → 'banking:write'
     *   'delete-common-items'     → 'items:delete'
     *   'read-admin-panel'        → null  (excluded)
     *   'read-crm-contacts'       → 'crm:read'  (future module, automatic)
     */
    public static function toScope(string $permission): ?string
    {
        // Split into action and resource: "read-sales-invoices" → ['read', 'sales-invoices']
        $dashPos = strpos($permission, '-');
        if ($dashPos === false) {
            return null;
        }

        $action   = substr($permission, 0, $dashPos);
        $resource = substr($permission, $dashPos + 1);

        // Map action to group
        $actionGroup = self::ACTION_MAP[$action] ?? null;
        if ($actionGroup === null) {
            return null;
        }

        // Check exclusion list
        if (in_array($resource, self::EXCLUDED, true)) {
            return null;
        }

        // Apply category alias (e.g. common-items → items)
        if (isset(self::CATEGORY_ALIASES[$resource])) {
            $scopeCategory = self::CATEGORY_ALIASES[$resource];
        } else {
            // Use the first segment of the resource as the scope category
            // "sales-invoices" → "sales",  "banking-accounts" → "banking"
            $scopeCategory = explode('-', $resource, 2)[0];
        }

        return $scopeCategory . ':' . $actionGroup;
    }

    // -------------------------------------------------------------------------
    // Scope → Permission patterns
    // -------------------------------------------------------------------------

    /**
     * Return the permission name patterns that a given scope covers.
     * Patterns may contain a '*' wildcard (compatible with Str::is).
     *
     * Examples:
     *   'sales:read'    → ['read-sales-*']
     *   'banking:write' → ['create-banking-*', 'update-banking-*']
     *   'items:delete'  → ['delete-common-items']   (alias reverse-lookup)
     *   'mcp:use'       → []   (manual scope, no permission mapping)
     */
    public static function toPermissionPatterns(string $scope): array
    {
        if (in_array($scope, self::MANUAL_SCOPES, true)) {
            return [];
        }

        $parts = explode(':', $scope, 2);
        if (count($parts) < 2) {
            return [];
        }

        [$scopeCategory, $actionGroup] = $parts;

        // Resolve alias back to the real category + feature
        $aliasedResource = null;
        foreach (self::CATEGORY_ALIASES as $resource => $alias) {
            if ($alias === $scopeCategory) {
                $aliasedResource = $resource; // e.g. "common-items"
                break;
            }
        }

        // Build action list for this group
        $actions = match ($actionGroup) {
            'read'   => ['read'],
            'write'  => ['create', 'update'],
            'delete' => ['delete'],
            default  => [],
        };

        $patterns = [];
        foreach ($actions as $action) {
            if ($aliasedResource !== null) {
                // Exact match via alias: delete-common-items
                $patterns[] = $action . '-' . $aliasedResource;
            } else {
                // Wildcard: read-sales-*
                $patterns[] = $action . '-' . $scopeCategory . '-*';
            }
        }

        return $patterns;
    }

    // -------------------------------------------------------------------------
    // Scope satisfaction checks
    // -------------------------------------------------------------------------

    /**
     * Check whether a single scope satisfies (covers) a given permission.
     *
     * Examples:
     *   scopeSatisfies('sales:read',  'read-sales-invoices')   → true
     *   scopeSatisfies('sales:write', 'create-sales-invoices') → true
     *   scopeSatisfies('sales:read',  'create-sales-invoices') → false
     *   scopeSatisfies('banking:read','read-sales-invoices')   → false
     */
    public static function scopeSatisfies(string $scope, string $permission): bool
    {
        foreach (self::toPermissionPatterns($scope) as $pattern) {
            if (Str::is($pattern, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether ANY scope in a list satisfies a given permission.
     */
    public static function anyScopeSatisfies(array $scopes, string $permission): bool
    {
        foreach ($scopes as $scope) {
            if (self::scopeSatisfies($scope, $permission)) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Scope discovery
    // -------------------------------------------------------------------------

    /**
     * Derive the full set of unique, API-exposable scope keys from every
     * permission currently stored in the database.
     *
     * New modules that follow the [action]-[module]-[feature] naming convention
     * appear here automatically — no code changes needed.
     *
     * Returns a sorted Collection of unique scope strings, e.g.:
     *   ['banking:delete', 'banking:read', 'banking:write', 'items:read', 'sales:read', ...]
     */
    public static function deriveAllScopes(): Collection
    {
        return Permission::pluck('name')
            ->map(fn (string $p) => self::toScope($p))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Derive the scope keys that a specific user is eligible to grant,
     * based on their actual Laratrust permissions (roles + direct).
     *
     * Used on the authorization screen: users cannot grant a scope for
     * a resource they do not have access to themselves.
     */
    public static function scopesForUser(User $user): Collection
    {
        return collect($user->allPermissions())
            ->pluck('name')
            ->map(fn (string $p) => self::toScope($p))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    // -------------------------------------------------------------------------
    // Human-readable descriptions
    // -------------------------------------------------------------------------

    /**
     * Return a human-readable description for a scope key.
     * Used in the authorization screen and the /oauth/scopes endpoint.
     *
     * Examples:
     *   'sales:read'    → 'View Sales data'
     *   'banking:write' → 'Create and update Banking data'
     *   'items:delete'  → 'Delete Items data'
     *   'mcp:use'       → 'Access MCP server capabilities and tools'
     */
    public static function describe(string $scope): string
    {
        if (in_array($scope, self::MANUAL_SCOPES, true)) {
            return match ($scope) {
                'mcp:use' => 'Access MCP server capabilities and tools',
                default   => $scope,
            };
        }

        [$category, $actionGroup] = array_pad(explode(':', $scope, 2), 2, '');

        $actionLabel = match ($actionGroup) {
            'read'   => 'View',
            'write'  => 'Create and update',
            'delete' => 'Delete',
            default  => ucfirst($actionGroup),
        };

        $categoryLabel = ucwords(str_replace('-', ' ', $category));

        return "{$actionLabel} {$categoryLabel} data";
    }

    /**
     * Return the full scope list with keys and descriptions, ready for
     * Passport::tokensCan() or the /oauth/scopes JSON response.
     *
     * Format: ['sales:read' => 'View Sales data', ...]
     */
    public static function allScopesWithDescriptions(): array
    {
        return self::deriveAllScopes()
            ->mapWithKeys(fn (string $scope) => [$scope => self::describe($scope)])
            ->all();
    }
}
