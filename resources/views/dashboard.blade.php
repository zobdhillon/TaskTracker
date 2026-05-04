<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ today()->format('l, F j, Y') }}
                </p>
            </div>

            <a href="{{ route('tasks.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                {{ __('New Task') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Completed Today -->
                <x-dashboard.stat-card :title="__('Completed Today')" :value="$completedToday" :trend="__('Tasks completed today')" color="blue">

                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <!-- Overdue -->
                <x-dashboard.stat-card :title="__('Overdue')" :value="$overdueTasks" :trend="$overdueTasks > 0 ? __('Needs attention') : __('All caught up!')" :color="$overdueTasks > 0 ? 'red' : 'green'">

                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <!-- Total Tasks -->
                <x-dashboard.stat-card :title="__('Total Tasks')" :value="$totalTasks" :trend="__('All tasks')" color="amber">

                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <!-- Completed (7 days) -->
                <x-dashboard.stat-card :title="__('Completed (7 days)')" :value="$completedLastSevenDays" :trend="__('Last 7 days')" color="green">

                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

            </div>

            <!-- Task Lists -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Overdue Tasks -->
                @if (!empty($overdueTasksList))
                    <x-dashboard.task-list :title="__('Overdue Tasks')" :tasks="$overdueTasksList" :emptyMessage="__('No overdue tasks')" variant="danger" />
                @endif

                <!-- Today Tasks -->
                <x-dashboard.task-list :title="__('Today Tasks')" :tasks="$todayTasksList" :emptyMessage="__('No tasks for today')" />

            </div>

            <!-- Quick Links -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ __('Quick Actions') }}
                    </h3>

                    <div class="flex flex-wrap gap-3 dark:text-white">

                        <a href="{{ route('tasks.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700  border rounded-md text-sm">
                            {{ __('View All Tasks') }}
                        </a>

                        <a href="{{ route('tasks.index', ['status' => \App\Enums\TaskStatus::Incomplete->value]) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border rounded-md text-sm">
                            {{ __('Incomplete Tasks') }}
                        </a>

                        <a href="{{ route('recurring-tasks.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border rounded-md text-sm">
                            {{ __('Recurring Tasks') }}
                        </a>

                        <a href="{{ route('categories.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border rounded-md text-sm">
                            {{ __('Categories') }}
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        @vite('resources/js/pages/dashboard.js')
    @endpush
</x-app-layout>
