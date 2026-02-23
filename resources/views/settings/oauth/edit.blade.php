<x-layouts.admin>
    <x-slot name="title">{{ trans('settings.oauth.title') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('settings.oauth.title') }}"
        icon="vpn_key"
        route="settings.oauth.edit"
    ></x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="setting" method="PATCH" route="settings.oauth.update">
                {{-- General Settings --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('settings.oauth.general') }}" 
                            description="{{ trans('settings.oauth.general_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.toggle 
                            name="enabled" 
                            label="{{ trans('settings.oauth.enabled') }}"
                            :value="setting('oauth.enabled', false)"
                        />

                        <x-form.group.toggle 
                            name="company_aware" 
                            label="{{ trans('settings.oauth.company_aware') }}"
                            :value="setting('oauth.company_aware', true)"
                        />

                        <x-form.group.toggle 
                            name="hash_client_secrets" 
                            label="{{ trans('settings.oauth.hash_client_secrets') }}"
                            :value="setting('oauth.hash_client_secrets', true)"
                        />

                        <div class="sm:col-span-6">
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span class="material-icons h-5 w-5 text-blue-400">info</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            {{ trans('settings.oauth.general_info') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-form.section>

                {{-- Security Settings --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('settings.oauth.security') }}" 
                            description="{{ trans('settings.oauth.security_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.toggle 
                            name="require_pkce" 
                            label="{{ trans('settings.oauth.require_pkce') }}"
                            :value="setting('oauth.require_pkce', true)"
                        />

                        <x-form.group.toggle 
                            name="require_audience" 
                            label="{{ trans('settings.oauth.require_audience') }}"
                            :value="setting('oauth.require_audience', false)"
                        />

                        <div class="sm:col-span-6">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span class="material-icons h-5 w-5 text-yellow-400">warning</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            {{ trans('settings.oauth.security_warning') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-form.section>

                {{-- Token Expiration --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('settings.oauth.token_expiration') }}" 
                            description="{{ trans('settings.oauth.token_expiration_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select
                            name="expiration_access_token"
                            label="{{ trans('settings.oauth.access_token_lifetime') }}"
                            :options="$lifetime_options"
                            :selected="setting('oauth.expiration.access_token', 60)"
                            not-required
                        />

                        <x-form.group.select
                            name="expiration_refresh_token"
                            label="{{ trans('settings.oauth.refresh_token_lifetime') }}"
                            :options="$lifetime_options"
                            :selected="setting('oauth.expiration.refresh_token', 20160)"
                            not-required
                        />

                        <x-form.group.select
                            name="expiration_personal_access_token"
                            label="{{ trans('settings.oauth.personal_token_lifetime') }}"
                            :options="$lifetime_options"
                            :selected="setting('oauth.expiration.personal_access_token', 525600)"
                            not-required
                        />
                    </x-slot>
                </x-form.section>

                {{-- Default Scopes --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('settings.oauth.default_scopes') }}" 
                            description="{{ trans('settings.oauth.default_scopes_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select
                            name="default_scope"
                            label="{{ trans('settings.oauth.default_scope') }}"
                            :options="$scope_options"
                            :selected="setting('oauth.default_scope', 'mcp:use')"
                            not-required
                        />

                        <div class="sm:col-span-6">
                            <p class="text-sm text-gray-600">
                                {{ trans('settings.oauth.default_scope_help') }}
                            </p>
                        </div>
                    </x-slot>
                </x-form.section>

                {{-- Dynamic Client Registration --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('settings.oauth.dcr') }}" 
                            description="{{ trans('settings.oauth.dcr_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.toggle 
                            name="dcr_enable_management" 
                            label="{{ trans('settings.oauth.dcr_enable_management') }}"
                            :value="setting('oauth.dcr.enable_management', false)"
                        />

                        <x-form.group.select
                            name="dcr_max_clients_per_ip"
                            label="{{ trans('settings.oauth.dcr_max_clients') }}"
                            :options="$dcr_max_options"
                            :selected="setting('oauth.dcr.max_clients_per_ip', 10)"
                            not-required
                        />

                        <x-form.group.text
                            name="dcr_client_expiration_days"
                            label="{{ trans('settings.oauth.dcr_expiration_days') }}"
                            :value="setting('oauth.dcr.client_expiration_days', 90)"
                            not-required
                        />

                        <div class="sm:col-span-6">
                            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span class="material-icons h-5 w-5 text-green-400">check_circle</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700">
                                            {{ trans('settings.oauth.dcr_info') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-form.section>

                @can('update-settings-defaults')
                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="dashboard" />
                    </x-slot>
                </x-form.section>
                @endcan

                <x-form.input.hidden name="_prefix" value="oauth" />
            </x-form>
        </x-form.container>
    </x-slot>

    <x-script folder="settings" file="settings" />
</x-layouts.admin>
