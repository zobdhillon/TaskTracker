<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
// use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7)->startOfDay();

        // Statistics
        $overdueTasks = $user->tasks()
            ->whereNull('completed_at')
            ->whereDate('task_date', '<', $today)
            ->count();

        $completedToday = $user->tasks()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '=', $today->toDateString())
            ->count();

        $completedLastSevenDays = $user->tasks()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $sevenDaysAgo->toDateString())
            ->count();

        $totalTasks = $user->tasks()->count();

        // Overdue tasks
        $overdueTasksList = $user->tasks()
            ->with('category')
            ->whereNull('completed_at')
            ->whereDate('task_date', '<', $today)
            ->orderBy('task_date', 'asc')
            ->get()
            ->map(fn ($task) => (new TaskResource($task))->resolve())
            ->toArray();

        // Today's tasks
        $todayTasksList = $user->tasks()
            ->with('category')
            ->whereDate('task_date', '=', $today->toDateString())
            ->orderByRaw('completed_at IS NULL DESC')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($task) => (new TaskResource($task))->resolve())
            ->toArray();

        return view('dashboard', [
            'overdueTasks' => $overdueTasks,
            'completedToday' => $completedToday,
            'completedLastSevenDays' => $completedLastSevenDays,
            'totalTasks' => $totalTasks,
            'overdueTasksList' => $overdueTasksList,
            'todayTasksList' => $todayTasksList,
        ]);
    }
}
