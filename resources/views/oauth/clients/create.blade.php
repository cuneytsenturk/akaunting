<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.new', ['type' => trans('oauth.client')]) }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('oauth.clients') }}"
        icon="key"
        route="oauth.clients.create"
    ></x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="oauth-client" route="oauth.clients.store">
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

                        <x-form.group.checkbox 
                            name="confidential" 
                            label="{{ trans('oauth.confidential_client') }}"
                            :options="['1' => trans('oauth.confidential_client_description')]"
                            form-group-class="sm:col-span-6"
                        />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('oauth.grant_types') }}" 
                            description="{{ trans('oauth.grant_types_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <div class="sm:col-span-6">
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-icon.info class="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            {{ trans('oauth.grant_type_info') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
