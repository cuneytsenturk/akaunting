<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Laravel\Passport\TokenRepository;

class Token extends Controller
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
     * Display a listing of the user's tokens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TokenRepository $tokens)
    {
        $user = $request->user();

        $userTokens = $tokens->forUser($user->getAuthIdentifier());

        // Filter by company if company_aware is enabled
        if (config('oauth.company_aware', true)) {
            $userTokens = collect($userTokens)->where('company_id', company_id())->values()->all();
        }

        return $this->response('auth.oauth.tokens', [
            'tokens' => $userTokens,
        ]);
    }

    /**
     * Revoke the given token.
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

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => trans('messages.success.deleted', ['type' => trans_choice('general.tokens', 1)]),
        ]);
    }
}
