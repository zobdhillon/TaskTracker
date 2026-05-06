<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\TaskFrequency;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateRecurringTasksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_creates_tasks_for_daily_recurring_task(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()
            ->for($user)
            ->daily()
            ->create(['start_date' => now()->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('tasks', ['user_id' => $user->id]);
    }

    #[Test]
    public function command_creates_tasks_for_multiple_recurring_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(3)
            ->for($user)
            ->daily()
            ->create(['start_date' => now()->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 3);
    }

    #[Test]
    public function command_does_not_create_task_if_already_exists_for_date(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()
            ->for($user)
            ->daily()
            ->create(['start_date' => now()->startOfDay()]);
        Task::factory()->for($user)->for($recurringTask)->create(['task_date' => today()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 1);
    }

    #[Test]
    public function command_skips_tasks_not_yet_started(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()
            ->for($user)
            ->daily()
            ->create(['start_date' => now()->addDays(5)->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function command_skips_tasks_past_end_date(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()
            ->for($user)
            ->daily()
            ->create([
                'start_date' => now()->subDays(10)->startOfDay(),
                'end_date' => now()->subDays(1)->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function command_creates_task_for_weekday_recurring_task_on_weekday(): void
    {
        $user = User::factory()->create();
        $weekday = now()->next('Monday');

        $this->travelTo($weekday);

        RecurringTask::factory()
            ->for($user)
            ->weekdays()
            ->create(['start_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 1);
    }

    #[Test]
    public function command_does_not_create_task_for_weekday_recurring_task_on_weekend(): void
    {
        $user = User::factory()->create();

        // Move to next Saturday
        $saturday = now()->next('Saturday');
        $this->travelTo($saturday);

        RecurringTask::factory()
            ->for($user)
            ->weekdays()
            ->create(['start_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        // Weekday-only tasks should not be created on Saturday
        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function command_creates_weekly_recurring_task_on_configured_days(): void
    {
        $user = User::factory()->create();
        $monday = now()->next('Monday');

        $this->travelTo($monday);

        RecurringTask::factory()
            ->for($user)
            ->create([
                'frequency' => TaskFrequency::Weekly->value,
                'frequency_config' => ['days' => ['Monday', 'Wednesday', 'Friday']],
                'start_date' => now()->subDay()->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 1);
    }

    #[Test]
    public function command_does_not_create_weekly_task_on_non_configured_days(): void
    {
        $user = User::factory()->create();
        $tuesday = now()->next('Tuesday');
        $this->travelTo($tuesday);

        RecurringTask::factory()
            ->for($user)
            ->create([
                'frequency' => TaskFrequency::Weekly->value,
                'frequency_config' => ['days' => ['Monday', 'Wednesday', 'Friday']],
                'start_date' => now()->subDay()->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function command_creates_monthly_recurring_task_on_configured_day(): void
    {
        $user = User::factory()->create();
        // Create a specific date on the 15th of current month
        $dateWithDay15 = now()->setDay(15)->startOfDay();
        $this->travelTo($dateWithDay15);

        RecurringTask::factory()
            ->for($user)
            ->create([
                'frequency' => TaskFrequency::Monthly->value,
                'frequency_config' => ['day_of_month' => 15],
                'start_date' => now()->subDay()->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 1);
    }

    #[Test]
    public function command_does_not_create_monthly_task_on_non_configured_day(): void
    {
        $user = User::factory()->create();
        $dateWithDay14 = now()->setDay(14)->startOfDay();

        $this->travelTo($dateWithDay14);

        RecurringTask::factory()
            ->for($user)
            ->create([
                'frequency' => TaskFrequency::Monthly->value,
                'frequency_config' => ['day_of_month' => 15],
                'start_date' => now()->subDay()->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function command_uses_recurring_task_details_for_created_task(): void
    {
        $user = User::factory()->create();
        $recurringTask = RecurringTask::factory()
            ->for($user)
            ->daily()
            ->create([
                'title' => 'Daily Standup',
                'description' => 'Team meeting',
                'start_date' => now()->startOfDay(),
            ]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Daily Standup',
            'description' => 'Team meeting',
            'recurring_task_id' => $recurringTask->id,
        ]);
    }

    #[Test]
    public function command_succeeds_when_no_active_tasks(): void
    {
        // Command returns SUCCESS (0) even when no tasks are found
        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();
    }

    #[Test]
    public function command_handles_multiple_batches_of_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(300)
            ->for($user)
            ->daily()
            ->create(['start_date' => now()->startOfDay()]);

        $this->artisan('app:generate-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseCount('tasks', 300);
    }
}
