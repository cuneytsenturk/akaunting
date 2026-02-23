<x-layouts.admin>
    <x-slot name="title">{{ trans('oauth.dashboard.title') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('oauth.dashboard.title') }}"
        icon="dashboard"
        route="settings.oauth.dashboard.index"
    ></x-slot>

    <x-slot name="buttons">
        <div class="flex items-center space-x-2">
            <select name="days" id="days-filter" class="rounded-md border-gray-300 shadow-sm text-sm" onchange="window.location.href='{{ route('settings.oauth.dashboard.index') }}?days=' + this.value">
                <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ trans('oauth.dashboard.last_7_days') }}</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ trans('oauth.dashboard.last_30_days') }}</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>{{ trans('oauth.dashboard.last_90_days') }}</option>
            </select>
        </div>
    </x-slot>

    <x-slot name="content">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Total Clients -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <span class="material-icons w-6 h-6">apps</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">
                            {{ trans('oauth.dashboard.total_clients') }}
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $stats['total_clients'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Tokens -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                            <span class="material-icons w-6 h-6">verified_user</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">
                            {{ trans('oauth.dashboard.active_tokens') }}
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $stats['active_tokens'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                            <span class="material-icons w-6 h-6">trending_up</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">
                            {{ trans('oauth.dashboard.recent_activity') }}
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $stats['recent_activity'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tokens Created -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-cyan-500 text-white">
                            <span class="material-icons w-6 h-6">add_circle</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">
                            {{ trans('oauth.dashboard.tokens_created') }}
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $stats['tokens_created'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tokens Revoked -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-500 text-white">
                            <span class="material-icons w-6 h-6">remove_circle</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">
                            {{ trans('oauth.dashboard.tokens_revoked') }}
                        </div>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $stats['tokens_revoked'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Activity Trend Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.activity_trend') }}
                </h3>
                <div class="h-64">
                    <canvas id="activityTrendChart"></canvas>
                </div>
            </div>

            <!-- Activity Breakdown Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.activity_breakdown') }}
                </h3>
                <div class="h-64">
                    <canvas id="activityBreakdownChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Token & Client Stats Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Token Expiration Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.token_status') }}
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ trans('oauth.dashboard.active') }}</span>
                        <span class="text-sm font-semibold text-green-600">{{ $tokenExpirationStats['active'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $tokenExpirationStats['percent_active'] }}%"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="border rounded p-3">
                            <div class="text-xs text-gray-500">{{ trans('oauth.dashboard.expiring_soon') }}</div>
                            <div class="text-lg font-semibold text-orange-600">{{ $tokenExpirationStats['expiring_soon'] }}</div>
                        </div>
                        <div class="border rounded p-3">
                            <div class="text-xs text-gray-500">{{ trans('oauth.dashboard.expired') }}</div>
                            <div class="text-lg font-semibold text-red-600">{{ $tokenExpirationStats['expired'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Type Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.client_types') }}
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded">
                        <span class="text-sm text-gray-700">{{ trans('oauth.dashboard.confidential_clients') }}</span>
                        <span class="text-sm font-semibold text-blue-600">{{ $clientTypeDistribution['confidential'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <span class="text-sm text-gray-700">{{ trans('oauth.dashboard.public_clients') }}</span>
                        <span class="text-sm font-semibold text-gray-600">{{ $clientTypeDistribution['public'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded">
                        <span class="text-sm text-gray-700">{{ trans('oauth.dashboard.personal_access') }}</span>
                        <span class="text-sm font-semibold text-purple-600">{{ $clientTypeDistribution['personal_access'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded">
                        <span class="text-sm text-gray-700">{{ trans('oauth.dashboard.password_grant') }}</span>
                        <span class="text-sm font-semibold text-green-600">{{ $clientTypeDistribution['password_grant'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Lists Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Most Active Clients -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.most_active_clients') }}
                </h3>
                @if($mostActiveClients->count())
                    <div class="space-y-3">
                        @foreach($mostActiveClients as $client)
                            <div class="flex items-center justify-between p-3 border rounded hover:bg-gray-50">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $client->client_name }}</div>
                                    <div class="text-xs text-gray-500">{{ trans('oauth.dashboard.activities_count', ['count' => $client->activity_count]) }}</div>
                                </div>
                                <div class="text-sm font-semibold text-blue-600">{{ $client->activity_count }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">{{ trans('oauth.dashboard.no_data') }}</p>
                @endif
            </div>

            <!-- Top Users by Tokens -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ trans('oauth.dashboard.top_users') }}
                </h3>
                @if($topUsers->count())
                    <div class="space-y-3">
                        @foreach($topUsers as $user)
                            <div class="flex items-center justify-between p-3 border rounded hover:bg-gray-50">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $user['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $user['email'] }}</div>
                                </div>
                                <div class="text-sm font-semibold text-green-600">{{ $user['token_count'] }} tokens</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">{{ trans('oauth.dashboard.no_data') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="script">
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            // Activity Trend Chart
            const activityTrendCtx = document.getElementById('activityTrendChart').getContext('2d');
            const activityTrendChart = new Chart(activityTrendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($activityTrend->pluck('formatted_date')) !!},
                    datasets: [{
                        label: '{{ trans('oauth.dashboard.daily_activities') }}',
                        data: {!! json_encode($activityTrend->pluck('count')) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Activity Breakdown Chart
            const activityBreakdownCtx = document.getElementById('activityBreakdownChart').getContext('2d');
            const activityBreakdownChart = new Chart(activityBreakdownCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($activityBreakdown->pluck('label')) !!},
                    datasets: [{
                        data: {!! json_encode($activityBreakdown->pluck('count')) !!},
                        backgroundColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(59, 130, 246)',
                            'rgb(168, 85, 247)',
                            'rgb(234, 179, 8)',
                            'rgb(236, 72, 153)',
                            'rgb(20, 184, 166)',
                            'rgb(249, 115, 22)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        </script>
    </x-slot>
</x-layouts.admin>
