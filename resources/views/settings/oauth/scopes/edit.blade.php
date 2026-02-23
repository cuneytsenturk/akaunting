<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.edit', ['type' => trans('oauth.scope')]) }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('oauth.scopes.title') }}"
        icon="label"
        route="settings.oauth.scopes.edit"
    ></x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="oauth-scope" method="PATCH" :route="['settings.oauth.scopes.update', $scope->id]" :model="$scope">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head 
                            title="{{ trans('oauth.scopes.scope_information') }}" 
                            description="{{ trans('oauth.scopes.scope_information_description') }}" 
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text 
                            name="key" 
                            label="{{ trans('oauth.scopes.scope_key') }}" 
                            placeholder="my-custom-scope"
                            form-group-class="sm:col-span-3"
                        />

                        <x-form.group.text 
                            name="name" 
                            label="{{ trans('general.name') }}" 
                            placeholder="{{ trans('oauth.scopes.scope_name_placeholder') }}"
                            form-group-class="sm:col-span-3"
                        />

                        <x-form.group.textarea 
                            name="description" 
                            label="{{ trans('general.description') }}"
                            placeholder="{{ trans('oauth.scopes.scope_description_placeholder') }}"
                            form-group-class="sm:col-span-6"
                            not-required
                        />

                        <x-form.group.select
                            name="group"
                            label="{{ trans('oauth.scopes.group') }}"
                            :options="$groups"
                            form-group-class="sm:col-span-3"
                            not-required
                        />

                        <x-form.group.text 
                            name="sort_order" 
                            label="{{ trans('oauth.scopes.sort_order') }}" 
                            form-group-class="sm:col-span-3"
                            not-required
                        />

                        <x-form.group.toggle 
                            name="enabled" 
                            label="{{ trans('general.enabled') }}"
                            form-group-class="sm:col-span-3"
                        />

                        <x-form.group.toggle 
                            name="is_default" 
                            label="{{ trans('oauth.scopes.is_default') }}"
                            form-group-class="sm:col-span-3"
                        />

                        <div class="sm:col-span-6">
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span class="material-icons h-5 w-5 text-blue-400">info</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            {{ trans('oauth.scopes.scope_help') }}
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
                        <x-form.buttons cancel-route="settings.oauth.scopes.index" />
                    </x-slot>
                </x-form.section>
                @endcan
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>
