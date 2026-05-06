<?php

namespace Tests\Feature\Controllers;

use App\Enums\TaskFrequency;
use App\Models\Category;
use App\Models\RecurringTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringTaskControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function guest_cannot_view_recurring_tasks(): void
    {
        $this->get(route('recurring-tasks.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_own_recurring_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(3)->for($user)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.index'));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.index');
        $response->assertViewHas('recurringTasks');
        $this->assertCount(3, $response->viewData('recurringTasks'));
    }

    #[Test]
    public function user_can_only_view_own_recurring_tasks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        RecurringTask::factory(2)->for($user1)->create();
        RecurringTask::factory(3)->for($user2)->create();

        $response = $this->actingAs($user1)->get(route('recurring-tasks.index'));

        $this->assertCount(2, $response->viewData('recurringTasks'));
    }

    #[Test]
    public function guest_cannot_access_create_recurring_task_form(): void
    {
        $this->get(route('recurring-tasks.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_recurring_task_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.create'));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.create');
        $response->assertViewHas('categories');
        $response->assertViewHas('frequencies');
    }

    #[Test]
    public function guest_cannot_create_recurring_task(): void
    {
        $this->post(route('recurring-tasks.store'), [
            'title' => 'Daily Task',
            'frequency' => 'daily',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_create_daily_recurring_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('recurring-tasks.store'), [
            'title' => 'Daily Standup',
            'description' => 'Team standup meeting',
            'frequency' => TaskFrequency::Daily->value,
            'category_id' => $category->uuid,
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('recurring-tasks.index'));
        $response->assertSessionHas('success', 'Recurring task created successfully.');
        $this->assertDatabaseHas('recurring_tasks', [
            'user_id' => $user->id,
            'title' => 'Daily Standup',
            'frequency' => TaskFrequency::Daily->value,
        ]);
    }

    #[Test]
    public function authenticated_user_can_create_weekly_recurring_task_with_config(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('recurring-tasks.store'), [
            'title' => 'Weekly Review',
            'frequency' => TaskFrequency::Weekly->value,
            'category_id' => $category->uuid,
            'start_date' => now()->toDateString(),
            'days' => ['monday', 'wednesday', 'friday'],
        ]);

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas('recurring_tasks', [
            'user_id' => $user->id,
            'title' => 'Weekly Review',
            'frequency' => TaskFrequency::Weekly->value,
        ]);
    }

    #[Test]
    public function authenticated_user_can_create_monthly_recurring_task_with_config(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('recurring-tasks.store'), [
            'title' => 'Monthly Report',
            'frequency' => TaskFrequency::Monthly->value,
            'category_id' => $category->uuid,
            'start_date' => now()->toDateString(),
            'day_of_month' => 15,
        ]);

        $response->assertRedirect(route('recurring-tasks.index'));
    }

    public static function invalidRecurringTaskDataProvider(): array
    {
        return [
            'missing title' => [['title' => '', 'frequency' => 'daily', 'start_date' => fake()->date()], 'title'],
            'invalid frequency' => [['title' => 'Task', 'frequency' => 'invalid', 'start_date' => fake()->date()], 'frequency'],
            'missing start_date' => [['title' => 'Task', 'frequency' => 'daily', 'start_date' => ''], 'start_date'],
        ];
    }

    #[Test]
    #[DataProvider('invalidRecurringTaskDataProvider')]
    public function recurring_task_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('recurring-tasks.store'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertDatabaseCount('recurring_tasks', 0);
    }

    #[Test]
    public function user_cannot_create_recurring_task_with_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->post(route('recurring-tasks.store'), [
            'title' => 'Recurring Task',
            'frequency' => 'daily',
            'category_id' => $category->uuid,
            'start_date' => now()->toDateString(),
        ]);

        $response->assertInvalid('category_id');
    }

    #[Test]
    public function guest_cannot_access_edit_recurring_task_form(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $this->get(route('recurring-tasks.edit', $recurringTask))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_access_another_users_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get(route('recurring-tasks.edit', $recurringTask))->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_view_edit_recurring_task_form(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.edit', $recurringTask));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.edit');
        $response->assertViewHas('recurringTask');
        $response->assertViewHas('frequencies');
    }

    #[Test]
    public function guest_cannot_update_recurring_task(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $this->put(route('recurring-tasks.update', $recurringTask), [
            'title' => 'Updated',
            'frequency' => 'daily',
            'start_date' => now()->toDateString(),
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_update_another_users_recurring_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $this->actingAs($otherUser)->put(route('recurring-tasks.update', $recurringTask), [
            'title' => 'Hacked',
            'frequency' => 'daily',
            'start_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_update_own_recurring_task(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();
        $newTitle = 'Updated Title';

        $response = $this->actingAs($user)->put(route('recurring-tasks.update', $recurringTask), [
            'title' => $newTitle,
            'frequency' => TaskFrequency::Weekdays->value,
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('recurring-tasks.index'));
        $response->assertSessionHas('success', 'Recurring task updated successfully.');
        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $recurringTask->id,
            'title' => $newTitle,
        ]);
    }

    #[Test]
    public function user_can_update_recurring_task_category(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();
        $newCategory = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('recurring-tasks.update', $recurringTask), [
            'title' => $recurringTask->title,
            'frequency' => TaskFrequency::Daily->value,
            'category_id' => $newCategory->uuid,
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $recurringTask->id,
            'category_id' => $newCategory->id,
        ]);
    }

    #[Test]
    public function user_cannot_update_recurring_task_with_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($owner)->put(route('recurring-tasks.update', $recurringTask), [
            'title' => $recurringTask->title,
            'frequency' => TaskFrequency::Daily->value,
            'category_id' => $otherCategory->uuid,
            'start_date' => now()->toDateString(),
        ]);

        $response->assertInvalid('category_id');
    }

    #[Test]
    public function guest_cannot_delete_recurring_task(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $this->delete(route('recurring-tasks.destroy', $recurringTask))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_delete_another_users_recurring_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $this->actingAs($otherUser)->delete(route('recurring-tasks.destroy', $recurringTask))->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_delete_own_recurring_task(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('recurring-tasks.destroy', $recurringTask));

        $response->assertNoContent();
        $this->assertSoftDeleted('recurring_tasks', ['id' => $recurringTask->id]);
    }

    #[Test]
    public function recurring_tasks_index_is_paginated(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(25)->for($user)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.index'));

        $response->assertOk();
        $this->assertCount(15, $response->viewData('recurringTasks'));
        $this->assertNotNull($response->viewData('links'));
    }

    #[Test]
    public function recurring_task_view_shows_all_frequency_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.create'));

        $frequencies = $response->viewData('frequencies');
        $this->assertCount(4, $frequencies);
        $this->assertContains(TaskFrequency::Daily, $frequencies);
        $this->assertContains(TaskFrequency::Weekdays, $frequencies);
        $this->assertContains(TaskFrequency::Weekly, $frequencies);
        $this->assertContains(TaskFrequency::Monthly, $frequencies);
    }
}
