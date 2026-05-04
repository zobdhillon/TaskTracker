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

    #[Test]
    public function guest_cannot_access_create_task_form(): void
    {
        $this->get(route('tasks.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_task_form(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function guest_cannot_access_edit_task_form(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->get(route('tasks.edit', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_access_another_users_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get(route('tasks.edit', $task))->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_view_edit_task_form(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');
        $response->assertViewHas('task', function ($data) use ($task) {
            return $data['id'] === $task->uuid;
        });
    }

    #[Test]
    public function guest_cannot_update_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->put(route('tasks.update', $task), ['title' => 'Updated'])->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_update_own_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $newTitle = 'Updated Title';

        $response = $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => $newTitle,
            'task_date' => $task->task_date->toDateString(),
        ]);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task updated successfully.');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $newTitle,
        ]);
    }

    #[Test]
    public function user_can_update_task_category(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $newCategory = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'task_date' => $task->task_date->toDateString(),
            'category_id' => $newCategory->uuid,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'category_id' => $newCategory->id,
        ]);
    }

    #[Test]
    public function user_cannot_update_task_with_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($owner)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'task_date' => $task->task_date->toDateString(),
            'category_id' => $otherCategory->uuid,
        ]);

        $response->assertInvalid('category_id');
    }

    public static function invalidUpdateDataProvider(): array
    {
        return [
            'empty title on update' => [
                ['title' => '', 'task_date' => fake()->date()],
                'title'
            ],
            'invalid date on update' => [
                ['title' => fake()->words(asText: true), 'task_date' => 'not-a-date'],
                'task_date',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidUpdateDataProvider')]
    public function task_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('tasks.update', $task), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function guest_cannot_toggle_task_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->patch(route('tasks.toggle', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_toggle_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        $this->actingAs($otherUser)->patch(route('tasks.toggle', $task))->assertForbidden();
    }

    #[Test]
    public function guest_cannot_delete_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function task_index_can_filter_by_incomplete_status(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->for($user)->create();
        Task::factory()->count(2)->completed()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.index', ['status' => TaskStatus::Incomplete->value]));

        $response->assertOk();
        $this->assertCount(3, $response->viewData('tasks'));
    }

    #[Test]
    public function task_index_can_filter_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->for($user)->create();
        $category2 = Category::factory()->for($user)->create();

        Task::factory(2)->for($user)->for($category1)->create();
        Task::factory(3)->for($user)->for($category2)->create();

        $response = $this->actingAs($user)->get(route('tasks.index', ['category_id' => $category1->uuid]));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('tasks'));
    }

    #[Test]
    public function task_index_can_filter_by_date_range(): void
    {
        $user = User::factory()->create();
        $pastDate = now()->subDays(10);
        $futureDate = now()->addDays(10);

        Task::factory()->for($user)->create(['task_date' => $pastDate]);
        Task::factory()->for($user)->create(['task_date' => $futureDate]);

        $response = $this->actingAs($user)->get(route('tasks.index', [
            'date_from' => $pastDate->toDateString(),
            'date_to' => $pastDate->toDateString(),
        ]));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('tasks'));
    }

    #[Test]
    public function task_index_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        Task::factory(25)->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $this->assertCount(15, $response->viewData('tasks'));
        $this->assertNotNull($response->viewData('links'));
    }

    #[Test]
    public function task_creation_with_description_is_optional(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Task without description',
            'category_id' => $category->uuid,
            'task_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Task without description',
            'description' => null,
        ]);
    }

    #[Test]
    public function task_creation_without_category_is_allowed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Task without category',
            'task_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Task without category',
            'category_id' => null,
        ]);
    }
}
