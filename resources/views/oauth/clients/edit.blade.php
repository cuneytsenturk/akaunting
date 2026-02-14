<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.edit', ['type' => trans('oauth.client')]) }}</x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="oauth-client" method="PATCH" :route="['oauth.clients.update', $client->id]" :model="$client">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('oauth.client_information') }}" 
                            description="{{ trans('oauth.client_information_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text 
                            name="name" 
                            label="{{ trans('general.name') }}" 
                            placeholder="{{ trans('oauth.client_name_placeholder') }}"
                            form-group-class="sm:col-span-6"
                        />

                        <x-form.group.text 
                            name="redirect" 
                            label="{{ trans('oauth.redirect_url') }}" 
                            placeholder="https://example.com/callback"
                            form-group-class="sm:col-span-6"
                        />

                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ trans('oauth.client_id') }}
                            </label>
                            <div class="flex items-center space-x-2">
                                <code class="flex-1 bg-gray-100 px-3 py-2 rounded text-sm">{{ $client->id }}</code>
                                <x-button 
                                    type="button" 
                                    kind="secondary"
                                    onclick="navigator.clipboard.writeText('{{ $client->id }}')"
                                >
                                    {{ trans('general.copy') }}
                                </x-button>
                            </div>
                        </div>

                        @if (!empty($client->secret))
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ trans('oauth.client_secret') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 bg-gray-100 px-3 py-2 rounded text-sm">••••••••••••••••</code>
                                    <x-form 
                                        id="regenerate-secret-{{ $client->id }}" 
                                        method="POST" 
                                        :route="['oauth.clients.secret', $client->id]"
                                    >
                                        <x-button type="submit" kind="danger">
                                            {{ trans('oauth.regenerate_secret') }}
                                        </x-button>
                                    </x-form>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ trans('oauth.secret_warning') }}
                                </p>
                            </div>
                        @endif
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="oauth.clients.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>

    <x-script folder="oauth" file="clients" />
</x-layouts.admin>
