<x-layouts.auth>
    <x-slot name="title">{{ trans('oauth.authorize_application') }}</x-slot>

    <x-slot name="content">
        <div class="flex flex-col items-center justify-center min-h-screen py-12">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <!-- Client Info -->
                    <div class="text-center mb-6">
                        <div class="mb-4">
                            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </span>
                        </div>
                        
                        <h2 class="text-2xl font-semibold text-gray-900 mb-2">
                            {{ trans('oauth.authorize_title') }}
                        </h2>
                        
                        <p class="text-sm text-gray-600">
                            <strong>{{ $client->name }}</strong> {{ trans('oauth.requests_access') }}
                        </p>
                    </div>

                    <!-- User Info -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <img src="{{ $user->picture }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full">
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Company Selection -->
                    @if (count($companies) > 0)
                        <form id="authorize-form" method="POST" action="{{ route('oauth.authorize.approve') }}">
                            @csrf
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">

                            @if (count($companies) > 1)
                                <div class="mb-6">
                                    <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ trans('oauth.select_company') }}
                                    </label>
                                    <select 
                                        name="company_id" 
                                        id="company_id" 
                                        required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">{{ trans('general.form.select.field', ['field' => trans_choice('general.companies', 1)]) }}</option>
                                        @foreach($companies as $companyId => $companyName)
                                            <option value="{{ $companyId }}" {{ $selectedCompanyId == $companyId ? 'selected' : '' }}>
                                                {{ $companyName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ trans('oauth.company_selection_info') }}
                                    </p>
                                </div>
                            @else
                                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            @endif
                    @endif

                    <!-- Scopes -->
                    @if (count($scopes) > 0)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                                {{ trans('oauth.will_be_able_to') }}:
                            </h3>
                            <ul class="space-y-2">
                                @foreach ($scopes as $scope)
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ is_object($scope) && isset($scope->description) ? $scope->description : $scope }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button 
                            type="button"
                            onclick="window.history.back()"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                        >
                            {{ trans('general.cancel') }}
                        </button>

                        <button 
                            type="submit" 
                            form="authorize-form"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                        >
                            {{ trans('oauth.authorize') }}
                        </button>
                    </div>

                    @if (count($companies) > 0)
                        </form>
                    @endif

                    <!-- Client Redirect Info -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-500 text-center">
                            {{ trans('oauth.redirect_info', ['url' => $client->redirect]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.auth>
