<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Services\OAuth\ScopeMapper;
use Illuminate\Http\Request;

class Scope extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth');
    }

    /**
     * Get all OAuth scopes available in this installation.
     *
     * When called by an authenticated user, the response is filtered to only
     * include the scopes that user is eligible to grant (based on their
     * Laratrust permissions). This prevents clients from discovering scopes
     * that the current user can never actually authorize.
     *
     * Response format (array of objects, compatible with Passport's default):
     *   [{ "id": "sales:read", "description": "View Sales data" }, ...]
     *
     * The special mcp:use scope is always included in the full list but is
     * only shown to the requesting user if they actually have a token with
     * that scope or it is listed in the manual scopes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Build the set of scopes relevant to this user based on their permissions.
        // Manual scopes (mcp:use) are always appended so clients can always
        // discover and request them.
        $userScopes = ScopeMapper::scopesForUser($user);

        // Append manual scopes that are not permission-derived
        foreach (ScopeMapper::MANUAL_SCOPES as $manualScope) {
            if (!$userScopes->contains($manualScope)) {
                $userScopes->push($manualScope);
            }
        }

        $data = $userScopes->sort()->values()->map(fn (string $key) => [
            'id'          => $key,
            'description' => ScopeMapper::describe($key),
        ])->all();

        return response()->json([
            'success' => true,
            'error'   => false,
            'data'    => $data,
        ]);
    }
}
