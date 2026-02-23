<x-layouts.admin>
    <x-slot name="title">{{ trans('oauth.activity.title') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('oauth.activity.title') }}"
        icon="history"
        route="settings.oauth.activity.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link.button
            href="{{ route('settings.oauth.activity.index') }}"
            override="class"
            class="px-3 py-1.5 mb-3 sm:mb-0 rounded-xl text-sm font-medium leading-6 bg-gray-200 hover:bg-gray-300 text-gray-700"
        >
            {{ trans('general.refresh') }}
        </x-link.button>
    </x-slot>

    <x-slot name="content">
        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form method="GET" action="{{ route('settings.oauth.activity.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ trans('oauth.activity.event_type') }}
                        </label>
                        <select name="event_type" class="w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($eventTypes as $value => $label)
                                <option value="{{ $value }}" {{ request('event_type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ trans('oauth.client') }}
                        </label>
                        <select name="client_id" class="w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($clients as $value => $label)
                                <option value="{{ $value }}" {{ request('client_id') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ trans('oauth.activity.recent_days') }}
                        </label>
                        <select name="days" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="7" {{ request('days') == 7 ? 'selected' : '' }}>{{ trans('oauth.activity.last_7_days') }}</option>
                            <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>{{ trans('oauth.activity.last_30_days') }}</option>
                            <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>{{ trans('oauth.activity.last_90_days') }}</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            {{ trans('general.filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if ($activities->count())
            <x-index.container>
                <x-table>
                    <x-table.thead>
                        <x-table.tr>
                            <x-table.th class="w-2/12">
                                {{ trans('oauth.activity.event') }}
                            </x-table.th>

                            <x-table.th class="w-2/12" hidden-mobile>
                                {{ trans('oauth.client') }}
                            </x-table.th>

                            <x-table.th class="w-3/12">
                                {{ trans('general.description') }}
                            </x-table.th>

                            <x-table.th class="w-2/12" hidden-mobile>
                                {{ trans('general.user') }}
                            </x-table.th>

                            <x-table.th class="w-2/12" hidden-mobile>
                                {{ trans('oauth.activity.ip_address') }}
                            </x-table.th>

                            <x-table.th class="w-1/12" hidden-mobile>
                                {{ trans('general.date') }}
                            </x-table.th>
                        </x-table.tr>
                    </x-table.thead>

                    <x-table.tbody>
                        @foreach($activities as $activity)
                            <x-table.tr>
                                <x-table.td class="w-2/12">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                            @if($activity->event_color === 'green') bg-green-100 text-green-800
                                            @elseif($activity->event_color === 'red') bg-red-100 text-red-800
                                            @elseif($activity->event_color === 'blue') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif
                                        ">
                                            <span class="material-icons w-4 h-4 mr-1">{{ $activity->event_icon }}</span>
                                            {{ $activity->event_type_label }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td class="w-2/12" hidden-mobile>
                                    {{ $activity->client_name ?? '-' }}
                                </x-table.td>

                                <x-table.td class="w-3/12">
                                    <span class="text-sm">{{ $activity->description }}</span>
                                </x-table.td>

                                <x-table.td class="w-2/12" hidden-mobile>
                                    {{ $activity->user?->name ?? '-' }}
                                </x-table.td>

                                <x-table.td class="w-2/12" hidden-mobile>
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                        {{ $activity->ip_address ?? '-' }}
                                    </code>
                                </x-table.td>

                                <x-table.td class="w-1/12" hidden-mobile>
                                    <span class="text-xs text-gray-600">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                </x-table.td>
                            </x-table.tr>
                        @endforeach
                    </x-table.tbody>
                </x-table>

                <x-pagination :items="$activities" />
            </x-index.container>
        @else
            <x-empty-page
                group="oauth"
                page="activity"
                text="{{ trans('oauth.activity.empty_text') }}"
            />
        @endif
    </x-slot>
</x-layouts.admin>
