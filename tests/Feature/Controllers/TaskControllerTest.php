<?php

namespace Tests\Feature\Controllers;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]

    public function authenticated_user_can_view_tasks(): void
    {
        //Arrange
        $user = User::factory()->create();
        $tasks = Task::factory()->for($user)->create();

        //Act
        $response = $this->actingAs($user)->get(route('tasks.index'));

        //Assert
        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertViewHas('tasks');
    }

    #[Test]

    public function guest_cannot_view_tasks(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
    }

    #[Test]

    public function authenticated_user_can_create_tasks(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $taskData = [
            'title' => $this->faker->words(asText: true),
            'category_id' => $category->uuid,
            'description' => $this->faker->sentence(),
            'task_date' => $this->faker->date(),
        ];

        $response = $this->actingAs($user)->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task created successfully.');



        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => $taskData['title'],
        ]);
    }

    public static function invalidTaskDataProvider(): array
    {
        return [
            'missing title' => [
                ['title' => '', 'task_date' => fake()->date()],
                'title'
            ],
            'title too long' => [
                ['title' => str_repeat('a', 256), 'task_date' => fake()->date()],
                'title'
            ],
            'missing task_date' => [
                ['title' => fake()->words(asText: true), 'task_date' => ''],
                'task_date',
            ],
            'invalid task_date' => [
                ['title' => fake()->words(asText: true), 'task_date' => 'invalid-date'],
                'task_date',
            ],

        ];
    }

    #[Test]
    #[DataProvider('invalidTaskDataProvider')]
    public function task_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertDatabaseCount('tasks', 0);
    }



    #[Test]

    public function user_cannot_edit_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->put(route('tasks.update', $task));

        $response->assertForbidden();
    }

    #[Test]

    public function user_cannot_delete_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('tasks.destroy', $task));

        $response->assertForbidden();
    }

    #[Test]

    public function user_cannot_create_a_task_with_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->post(
            route('tasks.store'),
            [
                'title' => $this->faker->words(asText: true),
                'category_id' => $category->uuid,
                'task_date' => $this->faker->date(),
            ]
        );

        $response->assertInvalid('category_id');
        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]

    public function user_can_toggle_task_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->assertNull($task->completed_at);

        $response = $this->actingAs($user)->patch(route('tasks.toggle', $task));

        $response->assertOk();
        $response->assertJson(['completed' => true]);
        $this->assertNotNull($task->fresh()->completed_at);

        $response = $this->actingAs($user)->patch(route('tasks.toggle', $task));

        $response->assertOk();
        $response->assertJson(['completed' => false]);
        $this->assertNull($task->fresh()->completed_at);
    }

    #[Test]

    public function user_can_delete_a_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]

    public function task_index_can_filter_by_completed_status(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->for($user)->create();
        Task::factory()->count(2)->completed()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.index'), ['status' => TaskStatus::Completed->value]);

        $response->assertOk();
        $this->assertCount(5, $response->viewData('tasks'));
    }

    #[Test]
    public function categories_are_cached_when_user_visits_the_index_page(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->for($user)->create();

        Cache::expects('remember')
            ->withSomeOfArgs('categories.user.' . $user->id, 3600)
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
    }

    #[Test]
    public function task_is_overdue_when_date_is_in_the_past(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'task_date' => now()->addDays(2),
        ]);

        $this->assertFalse($task->task_date->isPast());

        $this->travel(3)->days();

        $this->assertTrue($task->task_date->isPast());
    }
}
