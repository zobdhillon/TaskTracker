<?php

namespace App\Http\Controllers;

use App\Actions\Category\GetCategories;
use App\Actions\Category\ResolveCategory;
use App\Enums\TaskFrequency;
use App\Http\Requests\StoreRecurringTaskRequest;
use App\Http\Requests\UpdateRecurringTaskRequest;
use App\Http\Resources\RecurringTaskResource;
use App\Models\RecurringTask;
use Illuminate\Http\Request;

class RecurringTaskController
{
    public function __construct(private readonly GetCategories $getCategories) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->recurringTasks()
            ->with('category')
            ->latest();

        $recurringTasks = $query->paginate();

        $categories = $this->getCategories->execute($request->user()->id);

        return view('recurring-tasks.index', [
            'recurringTasks' => $recurringTasks->map(fn ($task) => (new RecurringTaskResource($task))->resolve())->toArray(),
            'links' => fn () => $recurringTasks->links(),
            'categories' => $categories,
        ]);
    }

    public function create(Request $request)
    {
        $categories = $this->getCategories->execute($request->user()->id);

        return view('recurring-tasks.create', [
            'categories' => $categories,
            'frequencies' => TaskFrequency::cases(),
        ]);
    }

    public function store(StoreRecurringTaskRequest $request, ResolveCategory $resolveCategory)
    {
        $taskData = $request->validated();

        $taskData['category_id'] = $resolveCategory->execute(
            $taskData['category_id'],
            $request->user()
        );

        $request->user()->recurringTasks()->create($taskData);

        return to_route('recurring-tasks.index')->with('success', 'Recurring task created successfully.');
    }

    public function edit(RecurringTask $recurringTask, Request $request)
    {
        $categories = $this->getCategories->execute($request->user()->id);

        return view('recurring-tasks.edit', [
            'recurringTask' => (new RecurringTaskResource($recurringTask))->resolve(),
            'categories' => $categories,
            'frequencies' => TaskFrequency::cases(),
        ]);
    }

    public function update(UpdateRecurringTaskRequest $request, RecurringTask $recurringTask, ResolveCategory $resolveCategory)
    {
        $validatedData = $request->validated();

        $validatedData['category_id'] = $resolveCategory->execute(
            $validatedData['category_id'] ?? null,
            $request->user()
        );

        $recurringTask->fill($validatedData);

        $recurringTask->save();

        return to_route('recurring-tasks.index')->with('success', 'Recurring task updated successfully.');
    }

    public function destroy(RecurringTask $recurringTask)
    {
        $recurringTask->delete();

        return response()->noContent();
    }
}
