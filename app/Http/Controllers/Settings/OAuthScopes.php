<?php

namespace App\Http\Controllers\Settings;

use App\Abstracts\Http\Controller;
use App\Models\OAuth\Scope;
use Illuminate\Http\Request;

class OAuthScopes extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:update-settings-defaults');
    }

    /**
     * Display a listing of the scopes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $scopes = Scope::ordered()->get();

        $groups = Scope::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group')
            ->toArray();

        return $this->response('settings.oauth.scopes.index', compact('scopes', 'groups'));
    }

    /**
     * Show the form for creating a new scope.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = $this->getGroupOptions();

        return $this->response('settings.oauth.scopes.create', compact('groups'));
    }

    /**
     * Store a newly created scope.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100|unique:oauth_scopes,key|regex:/^[a-z0-9:_-]+$/i',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group' => 'nullable|string|max:50',
            'enabled' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'key.regex' => trans('oauth.scopes.key_format_error'),
            'key.unique' => trans('oauth.scopes.key_exists'),
        ]);

        $scope = Scope::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'group' => $validated['group'] ?? 'custom',
            'enabled' => $validated['enabled'] ?? true,
            'is_default' => $validated['is_default'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $message = trans('messages.success.added', ['type' => trans('oauth.scope')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.oauth.scopes.index'),
        ]);
    }

    /**
     * Show the form for editing the specified scope.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $scope = Scope::findOrFail($id);
        $groups = $this->getGroupOptions();

        return $this->response('settings.oauth.scopes.edit', compact('scope', 'groups'));
    }

    /**
     * Update the specified scope.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $scope = Scope::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|max:100|regex:/^[a-z0-9:_-]+$/i|unique:oauth_scopes,key,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group' => 'nullable|string|max:50',
            'enabled' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'key.regex' => trans('oauth.scopes.key_format_error'),
            'key.unique' => trans('oauth.scopes.key_exists'),
        ]);

        $scope->update([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'group' => $validated['group'] ?? 'custom',
            'enabled' => $validated['enabled'] ?? $scope->enabled,
            'is_default' => $validated['is_default'] ?? $scope->is_default,
            'sort_order' => $validated['sort_order'] ?? $scope->sort_order,
        ]);

        $message = trans('messages.success.updated', ['type' => trans('oauth.scope')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.oauth.scopes.index'),
        ]);
    }

    /**
     * Remove the specified scope.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $scope = Scope::findOrFail($id);

        $scope->delete();

        $message = trans('messages.success.deleted', ['type' => trans('oauth.scope')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.oauth.scopes.index'),
        ]);
    }

    /**
     * Enable the specified scope.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function enable($id)
    {
        $scope = Scope::findOrFail($id);
        $scope->update(['enabled' => true]);

        $message = trans('messages.success.enabled', ['type' => trans('oauth.scope')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.oauth.scopes.index'),
        ]);
    }

    /**
     * Disable the specified scope.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function disable($id)
    {
        $scope = Scope::findOrFail($id);
        $scope->update(['enabled' => false]);

        $message = trans('messages.success.disabled', ['type' => trans('oauth.scope')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('settings.oauth.scopes.index'),
        ]);
    }

    /**
     * Get group options for select box.
     *
     * @return array
     */
    protected function getGroupOptions()
    {
        return [
            'basic' => trans('oauth.scopes.group_basic'),
            'advanced' => trans('oauth.scopes.group_advanced'),
            'mcp' => trans('oauth.scopes.group_mcp'),
            'custom' => trans('oauth.scopes.group_custom'),
        ];
    }
}
