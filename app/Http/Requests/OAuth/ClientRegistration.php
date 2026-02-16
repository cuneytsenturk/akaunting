<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegistration extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // DCR endpoint is public, no authorization required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * RFC 7591: Dynamic Client Registration Protocol
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Required fields
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => 'required|url|regex:/^https:\/\//',

            // Optional client metadata
            'client_name' => 'nullable|string|max:255',
            'client_uri' => 'nullable|url',
            'logo_uri' => 'nullable|url',
            'tos_uri' => 'nullable|url',
            'policy_uri' => 'nullable|url',
            'contacts' => 'nullable|array',
            'contacts.*' => 'email',

            // Token endpoint authentication method
            'token_endpoint_auth_method' => 'nullable|in:client_secret_basic,client_secret_post,none',

            // Grant types
            'grant_types' => 'nullable|array',
            'grant_types.*' => 'in:authorization_code,refresh_token',

            // Response types
            'response_types' => 'nullable|array',
            'response_types.*' => 'in:code',

            // Scopes
            'scope' => 'nullable|string',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'redirect_uris.required' => 'At least one redirect URI is required',
            'redirect_uris.*.url' => 'Each redirect URI must be a valid URL',
            'redirect_uris.*.regex' => 'Redirect URIs must use HTTPS protocol',
            'token_endpoint_auth_method.in' => 'Invalid authentication method',
            'grant_types.*.in' => 'Invalid grant type',
            'response_types.*.in' => 'Invalid response type',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'redirect_uris' => 'redirect URIs',
            'client_name' => 'client name',
            'client_uri' => 'client homepage',
            'logo_uri' => 'logo URL',
            'tos_uri' => 'terms of service URL',
            'policy_uri' => 'privacy policy URL',
        ];
    }
}
