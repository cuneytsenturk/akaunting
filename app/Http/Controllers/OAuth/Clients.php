<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Models\OAuth\Client;
use App\Models\OAuth\AccessToken;
use Illuminate\Http\Request;

class Clients extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:read-auth-profile')->only('index', 'show');
    }

    /**
     * Display a listing of user's OAuth clients.
     */
    public function index()
    {
        $user = auth()->user();

        $clients = Client::where('user_id', $user->id)
            ->orWhere(function ($query) use ($user) {
                $query->whereHas('tokens', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->with(['tokens' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('revoked', false)
                      ->latest();
            }])
            ->latest()
            ->paginate(20);

        return $this->response('oauth.clients.index', compact('clients'));
    }

    /**
     * Show detailed information about an OAuth client.
     */
    public function show($id)
    {
        $user = auth()->user();

        $client = Client::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('tokens', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->firstOrFail();

        $activeTokens = AccessToken::where('client_id', $client->id)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->with('scopes')
            ->latest()
            ->get();

        $revokedTokens = AccessToken::where('client_id', $client->id)
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('revoked', true)
                      ->orWhere('expires_at', '<=', now());
            })
            ->latest()
            ->limit(10)
            ->get();

        return $this->response('oauth.clients.show', compact('client', 'activeTokens', 'revokedTokens'));
    }

    /**
     * Revoke all active tokens for a client.
     */
    public function revoke(Request $request, $id)
    {
        $user = auth()->user();

        $client = Client::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('tokens', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->firstOrFail();

        // Revoke all active tokens for this client for the current user
        $revokedCount = AccessToken::where('client_id', $client->id)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);

        $message = trans('oauth.access_revoked', [
            'name' => $client->name,
            'count' => $revokedCount,
        ]);

        flash($message)->success();

        return redirect()->route('oauth.clients.index');
    }

    /**
     * Delete a dynamically registered client.
     */
    public function destroy($id)
    {
        $user = auth()->user();

        $client = Client::where('id', $id)
            ->where('user_id', $user->id)
            ->whereIn('provider', [null, 'dcr']) // Only allow deletion of dynamic clients
            ->firstOrFail();

        // Delete associated tokens
        AccessToken::where('client_id', $client->id)->delete();

        // Delete the client
        $client->delete();

        $message = trans('oauth.client_deleted', ['name' => $client->name]);

        flash($message)->success();

        return redirect()->route('oauth.clients.index');
    }
}
