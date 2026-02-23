<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use App\Events\OAuth\ClientCreated;
use App\Events\OAuth\ClientDeleted;
use App\Events\OAuth\ClientSecretRegenerated;
use App\Events\OAuth\ClientUpdated;
use App\Models\OAuth\Client as ClientModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

class Client extends Controller
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
        $this->middleware('permission:create-auth-users')->only('create', 'store');
        $this->middleware('permission:read-auth-users')->only('index', 'show');
        $this->middleware('permission:update-auth-users')->only('edit', 'update');
        $this->middleware('permission:delete-auth-users')->only('destroy');
    }

    /**
     * Display a listing of the clients.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ClientModel::with('user');

        // Company scope is automatically applied via global scope
        $clients = $query->orderBy('created_at', 'desc')->get();

        return $this->response('auth.oauth.clients.index', [
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for creating a new client.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->response('auth.oauth.clients.create');
    }

    /**
     * Store a newly created client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ClientRepository $clients)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'redirect' => 'required|string',
            'confidential' => 'boolean',
        ]);

        // Support multiple redirect URLs (comma-separated or JSON array)
        $redirectUrls = $this->parseRedirectUrls($validated['redirect']);

        if (empty($redirectUrls)) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('oauth.invalid_redirect_urls'),
            ], 422);
        }

        // Store as JSON array for multiple URLs or single URL
        $validated['redirect'] = count($redirectUrls) > 1 ? json_encode($redirectUrls) : $redirectUrls[0];

        $client = $clients->create(
            user_id(),
            $validated['name'],
            $validated['redirect'],
            null,
            false,
            false,
            !$request->filled('confidential')
        );

        // Set company_id and created_from
        if (config('oauth.company_aware', true)) {
            $client->company_id = company_id();
        }

        $client->created_from = 'oauth.web';
        $client->created_by = user_id();
        $client->save();

        // Fire event
        event(new ClientCreated($client, !$request->filled('confidential')));

        $message = trans('messages.success.added', ['type' => trans_choice('general.clients', 1)]);

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'data' => [
                'client' => $client,
            ],
            'redirect' => route('oauth.clients.index'),
        ]);
    }

    /**
     * Display the specified client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $client_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $client_id)
    {
        $client = ClientModel::with('user')->findOrFail($client_id);

        return $this->response('auth.oauth.clients.show', [
            'client' => $client,
        ]);
    }

    /**
     * Show the form for editing the specified client.
     *
     * @param  string  $client_id
     * @return \Illuminate\Http\Response
     */
    public function edit($client_id)
    {
        $client = ClientModel::findOrFail($client_id);

        return $this->response('auth.oauth.clients.edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update the specified client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  string  $client_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ClientRepository $clients, $client_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'redirect' => 'required|string',
        ]);

        // Support multiple redirect URLs (comma-separated or JSON array)
        $redirectUrls = $this->parseRedirectUrls($validated['redirect']);

        if (empty($redirectUrls)) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('oauth.invalid_redirect_urls'),
            ], 422);
        }

        // Store as JSON array for multiple URLs or single URL
        $validated['redirect'] = count($redirectUrls) > 1 ? json_encode($redirectUrls) : $redirectUrls[0];

        $client = ClientModel::findOrFail($client_id);
        $original = $client->getOriginal();

        $clients->update($client, $validated['name'], $validated['redirect']);

        // Fire event
        event(new ClientUpdated($client->fresh(), $original));

        $message = trans('messages.success.updated', ['type' => trans_choice('general.clients', 1)]);

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'data' => [
                'client' => $client->fresh(),
            ],
            'redirect' => route('oauth.clients.index'),
        ]);
    }

    /**
     * Remove the specified client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  string  $client_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClientRepository $clients, $client_id)
    {
        $client = ClientModel::findOrFail($client_id);

        // Fire event before deletion
        event(new ClientDeleted($client));

        $clients->delete($client);

        $message = trans('messages.success.deleted', ['type' => trans_choice('general.clients', 1)]);

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('oauth.clients.index'),
        ]);
    }

    /**
     * Generate a new client secret.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  string  $client_id
     * @return \Illuminate\Http\Response
     */
    public function secret(ClientRepository $clients, $client_id)
    {
        $client = ClientModel::findOrFail($client_id);

        $client->secret = hash('sha256', $plainSecret = Str::random(40));
        $client->save();

        // Fire event
        event(new ClientSecretRegenerated($client, $plainSecret));

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => trans('messages.success.updated', ['type' => trans_choice('general.clients', 1)]),
            'data' => [
                'secret' => $plainSecret,
            ],
        ]);
    }

    /**
     * Parse and validate redirect URLs from input.
     * Supports comma-separated URLs or JSON array.
     *
     * @param  string  $input
     * @return array
     */
    protected function parseRedirectUrls(string $input): array
    {
        $urls = [];

        // Try to decode as JSON first
        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            $urls = $decoded;
        } else {
            // Split by comma, newline, or space
            $urls = preg_split('/[\s,\n\r]+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        }

        // Validate each URL
        $validUrls = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $validUrls[] = $url;
            }
        }

        return $validUrls;
    }
}
