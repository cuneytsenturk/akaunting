<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
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
            'redirect' => 'required|url',
            'confidential' => 'boolean',
        ]);

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
            'redirect' => 'required|url',
        ]);

        $client = ClientModel::findOrFail($client_id);

        $clients->update($client, $validated['name'], $validated['redirect']);

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

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => trans('messages.success.updated', ['type' => trans_choice('general.clients', 1)]),
            'data' => [
                'secret' => $plainSecret,
            ],
        ]);
    }
}
