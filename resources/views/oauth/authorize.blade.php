<x-layouts.admin>
    <x-slot name="title">{{ trans('oauth.authorize_application') }}</x-slot>

    <x-slot name="content">
        <div class="flex flex-col items-center justify-center min-h-screen py-12">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <!-- Client Info -->
                    <div class="text-center mb-6">
                        <div class="mb-4">
                            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100">
                                <x-icon.key class="w-8 h-8 text-blue-600" />
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

                    <!-- Scopes -->
                    @if (count($scopes) > 0)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">
                                {{ trans('oauth.will_be_able_to') }}:
                            </h3>
                            <ul class="space-y-2">
                                @foreach ($scopes as $scope)
                                    <li class="flex items-start">
                                        <x-icon.check class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                                        <span class="text-sm text-gray-700">{{ $scope->description }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('oauth.authorize.deny') }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            
                            <x-button kind="secondary" class="w-full" override="class">
                                {{ trans('general.cancel') }}
                            </x-button>
                        </form>

                        <form method="POST" action="{{ route('oauth.authorize.approve') }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            
                            <x-button type="submit" class="w-full">
                                {{ trans('oauth.authorize') }}
                            </x-button>
                        </form>
                    </div>

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
</x-layouts.admin>
