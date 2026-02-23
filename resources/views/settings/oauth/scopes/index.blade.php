<x-layouts.admin>
    <x-slot name="title">{{ trans('oauth.scopes.title') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('oauth.scopes.title') }}"
        icon="label"
        route="settings.oauth.scopes.index"
    ></x-slot>

    <x-slot name="buttons">
        @can('update-settings-defaults')
            <x-link href="{{ route('settings.oauth.scopes.create') }}" kind="primary">
                {{ trans('general.title.new', ['type' => trans('oauth.scope')]) }}
            </x-link>
        @endcan
    </x-slot>

    <x-slot name="content">
        @if ($scopes->count())
            <x-index.container>
                <x-table>
                    <x-table.thead>
                        <x-table.tr>
                            <x-table.th class="w-2/12">
                                {{ trans('oauth.scopes.scope_key') }}
                            </x-table.th>

                            <x-table.th class="w-3/12">
                                {{ trans('general.name') }}
                            </x-table.th>

                            <x-table.th class="w-4/12" hidden-mobile>
                                {{ trans('general.description') }}
                            </x-table.th>

                            <x-table.th class="w-1/12" hidden-mobile>
                                {{ trans('oauth.scopes.group') }}
                            </x-table.th>

                            <x-table.th class="w-1/12" hidden-mobile>
                                {{ trans('general.status') }}
                            </x-table.th>

                            <x-table.th class="w-1/12" kind="action">
                                {{ trans('general.actions') }}
                            </x-table.th>
                        </x-table.tr>
                    </x-table.thead>

                    <x-table.tbody>
                        @foreach($scopes as $scope)
                            <x-table.tr href="{{ route('settings.oauth.scopes.edit', $scope->id) }}">
                                <x-table.td class="w-2/12">
                                    <div class="flex items-center">
                                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $scope->key }}</code>
                                        @if ($scope->is_default)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ trans('oauth.scopes.default') }}
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td class="w-3/12">
                                    {{ $scope->name }}
                                </x-table.td>

                                <x-table.td class="w-4/12" hidden-mobile>
                                    <span class="text-sm text-gray-600">{{ Str::limit($scope->description, 60) }}</span>
                                </x-table.td>

                                <x-table.td class="w-1/12" hidden-mobile>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        @if($scope->group === 'mcp') bg-purple-100 text-purple-800
                                        @elseif($scope->group === 'advanced') bg-red-100 text-red-800
                                        @elseif($scope->group === 'basic') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif
                                    ">
                                        {{ trans('oauth.scopes.group_' . $scope->group) }}
                                    </span>
                                </x-table.td>

                                <x-table.td class="w-1/12" hidden-mobile>
                                    @if ($scope->enabled)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {{ trans('general.enabled') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ trans('general.disabled') }}
                                        </span>
                                    @endif
                                </x-table.td>

                                <x-table.td class="w-1/12" kind="action">
                                    <x-table.actions :model="$scope" />
                                </x-table.td>
                            </x-table.tr>
                        @endforeach
                    </x-table.tbody>
                </x-table>
            </x-index.container>
        @else
            <x-empty-page
                group="oauth"
                page="scopes"
                text="{{ trans('oauth.scopes.empty_text') }}"
                :buttons="[
                    'new' => [
                        'url' => route('settings.oauth.scopes.create'),
                        'permission' => 'update-settings-defaults',
                    ],
                ]"
            />
        @endif
    </x-slot>
</x-layouts.admin>
