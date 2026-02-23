<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Events\OAuth\TokenCreated;
use App\Events\OAuth\TokenRevoked;
use App\Models\OAuth\PersonalAccessClient as PersonalAccessClientModel;
use Illuminate\Http\Request;
use Laravel\Passport\TokenRepository;

class PersonalAccessToken extends Controller
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
     * Create a new personal access token for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'scopes' => 'array',
        ]);

        $user = $request->user();

        // Get or create a personal access client for this company
        $personalAccessClient = PersonalAccessClientModel::where('company_id', company_id())->first();

        if (!$personalAccessClient) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('oauth::messages.no_personal_access_client'),
            ], 400);
        }

        $token = $user->createToken(
            $validated['name'],
            $validated['scopes'] ?? []
        );

        // Update token with company_id and created_from
        $accessToken = $token->token;
        if (config('oauth.company_aware', true)) {
            $accessToken->company_id = company_id();
        }

        $accessToken->created_from = 'oauth.web';
        $accessToken->created_by = user_id();
        $accessToken->save();

        // Fire event
        event(new TokenCreated($accessToken, $personalAccessClient->client, [
            'grant_type' => 'personal_access',
            'token_name' => $validated['name'],
        ]));

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => trans('messages.success.added', ['type' => trans_choice('general.tokens', 1)]),
            'data' => [
                'access_token' => $token->accessToken,
                'token' => $token->token,
            ],
        ]);
    }

    /**
     * Revoke the given personal access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  string  $token_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, TokenRepository $tokens, $token_id)
    {
        $user = $request->user();

        $token = $tokens->find($token_id);

        if (!$token || $token->user_id !== $user->getAuthIdentifier()) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('general.error.not_in_company'),
            ], 404);
        }

        // Check company ownership if enabled
        if (config('oauth.company_aware', true) && $token->company_id !== company_id()) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('general.error.not_in_company'),
            ], 403);
        }

        $tokens->revokeAccessToken($token_id);

        // Fire event
        event(new TokenRevoked(
            $token_id,
            $token->client_id ?? null,
            $user->getAuthIdentifier()
        ));

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => trans('messages.success.deleted', ['type' => trans_choice('general.tokens', 1)]),
        ]);
    }
}
