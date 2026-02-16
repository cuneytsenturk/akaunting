<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'auth_token' => 'required|string',
            'company_id' => 'required|integer|exists:companies,id',
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
            'auth_token.required' => trans('general.invalid_token'),
            'company_id.required' => trans('oauth.company_selection_required'),
            'company_id.exists' => trans('general.error.not_in_company'),
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
            'auth_token' => 'authorization token',
            'company_id' => 'company',
        ];
    }

    /**
     * Validate company access for the authenticated user.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Ensure user has access to the selected company
        if ($this->company_id) {
            $hasAccess = auth()->user()
                ->companies()
                ->where('id', $this->company_id)
                ->exists();

            if (!$hasAccess) {
                abort(403, trans('general.error.not_in_company'));
            }
        }
    }
}
