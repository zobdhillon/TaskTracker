<?php

namespace Tests\Unit\Actions\Task;

use App\Actions\Task\CreateTask;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTaskActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_is_created_with_correct_attributes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $createTask = app(CreateTask::class);

        $task = $createTask->execute([
            'title' => 'Buy groceries',
            'description' => 'Milk, eggs, bread',
            'category_id' => $category->uuid,
            'task_date' => now()->toDateString(),
        ], $user);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Buy groceries', $task->title);
        $this->assertEquals('Milk, eggs, bread', $task->description);
        $this->assertEquals($category->id, $task->category_id);
        $this->assertEquals($user->id, $task->user_id);
    }

    #[Test]
    public function task_is_persisted_to_database(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $createTask = app(CreateTask::class);

        $createTask->execute([
            'title' => 'Complete project',
            'category_id' => $category->uuid,
            'task_date' => now()->toDateString(),
        ], $user);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Complete project',
        ]);
    }

    #[Test]
    public function task_can_be_created_without_category(): void
    {
        $user = User::factory()->create();
        $createTask = app(CreateTask::class);

        $task = $createTask->execute([
            'title' => 'Task without category',
            'task_date' => now()->toDateString(),
        ], $user);

        $this->assertNull($task->category_id);
    }

    #[Test]
    public function returns_created_task(): void
    {
        $user = User::factory()->create();
        $createTask = app(CreateTask::class);

        $task = $createTask->execute([
            'title' => 'Return test task',
            'task_date' => now()->toDateString(),
        ], $user);

        $this->assertNotNull($task->id);
        $this->assertInstanceOf(Task::class, $task);
    }

    #[Test]
    public function throws_validation_exception_when_category_not_owned_by_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();
        $createTask = app(CreateTask::class);

        $this->expectException(ValidationException::class);
        $createTask->execute([
            'title' => 'Task',
            'category_id' => $category->uuid,
            'task_date' => now()->toDateString(),
        ], $otherUser);
    }

    #[Test]
    public function task_has_correct_task_date(): void
    {
        $user = User::factory()->create();
        $createTask = app(CreateTask::class);
        $taskDate = now()->addDays(5)->toDateString();

        $task = $createTask->execute([
            'title' => 'Future task',
            'task_date' => $taskDate,
        ], $user);

        $this->assertEquals($taskDate, $task->task_date->toDateString());
    }
}
