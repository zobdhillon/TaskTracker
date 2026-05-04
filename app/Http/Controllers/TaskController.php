<?php

namespace App\Http\Controllers;

use App\Actions\Category\GetCategories;
use App\Actions\Category\ResolveCategory;
use App\Actions\Task\CreateTask;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Category;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController
{
    public function __construct(private readonly GetCategories $getCategories) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->tasks()->with('category')
            ->when($request->status === TaskStatus::Completed->value, fn ($q) => $q->whereNotNull('completed_at'))
            ->when($request->status === TaskStatus::Incomplete->value, fn ($q) => $q->whereNull('completed_at'))
            ->when($request->category_id, function ($q) use ($request) {
                $categoryId = Category::where('uuid', $request->category_id)->value('id');

                if ($categoryId) {
                    $q->where('category_id', $categoryId);
                }
            })
            ->when($request->date_from, fn ($q) => $q->whereDate('task_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('task_date', '<=', $request->date_to))
            ->latest();

        $tasks = $query->paginate();

        $categories = $this->getCategories->execute($request->user()->id);

        return view('tasks.index', [
            'tasks' => $tasks->map(fn ($task) => (new TaskResource($task))->resolve())->toArray(),
            'links' => fn () => $tasks->links(),
            'categories' => $categories,
            'filters' => $request->only(['status', 'category_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(Request $request)
    {
        $categories = $this->getCategories->execute($request->user()->id);

        return view('tasks.create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreTaskRequest $request, CreateTask $createTask)
    {
        $createTask->execute($request->validated(), $request->user());

        return to_route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task, Request $request)
    {
        $categories = $this->getCategories->execute($request->user()->id);

        return view('tasks.edit', [
            'task' => (new TaskResource($task))->resolve(),
            'categories' => $categories,
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, ResolveCategory $resolveCategory)
    {
        $validatedData = $request->validated();
        $validatedData['category_id'] = $resolveCategory->execute(
            $validatedData['category_id'],
            $request->user()
        );

        $task->fill($validatedData);

        $task->save();

        return to_route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }

    public function toggle(Task $task)
    {
        $task->toggleCompletion();

        $task->save();

        return response()->json(['completed' => $task->completed_at !== null]);
    }
}
