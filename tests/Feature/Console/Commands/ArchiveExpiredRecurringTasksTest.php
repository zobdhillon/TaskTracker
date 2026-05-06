<?php

namespace Tests\Feature\Console\Commands;

use App\Models\RecurringTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchiveExpiredRecurringTasksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_deletes_expired_recurring_tasks(): void
    {
        $user = User::factory()->create();
        $expiredTask = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertSoftDeleted('recurring_tasks', ['id' => $expiredTask->id]);
    }

    #[Test]
    public function command_deletes_multiple_expired_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(5)
            ->for($user)
            ->create(['end_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertEquals(5, RecurringTask::onlyTrashed()->count());
    }

    #[Test]
    public function command_does_not_delete_active_tasks(): void
    {
        $user = User::factory()->create();
        $activeTask = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->addDay()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseHas('recurring_tasks', ['id' => $activeTask->id]);
    }

    #[Test]
    public function command_does_not_delete_tasks_without_end_date(): void
    {
        $user = User::factory()->create();
        $noEndDateTask = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => null]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseHas('recurring_tasks', ['id' => $noEndDateTask->id]);
    }

    #[Test]
    public function command_does_not_delete_tasks_ending_today(): void
    {
        $user = User::factory()->create();
        $taskEndingToday = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertDatabaseHas('recurring_tasks', ['id' => $taskEndingToday->id]);
    }

    #[Test]
    public function command_deletes_tasks_ending_in_the_past(): void
    {
        $user = User::factory()->create();
        $taskEndedYesterday = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertSoftDeleted('recurring_tasks', ['id' => $taskEndedYesterday->id]);
    }

    #[Test]
    public function command_handles_mixed_expired_and_active_tasks(): void
    {
        $user = User::factory()->create();
        $expiredTask = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->subDays(5)->startOfDay()]);
        $activeTask = RecurringTask::factory()
            ->for($user)
            ->create(['end_date' => now()->addDays(5)->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();

        $this->assertSoftDeleted('recurring_tasks', ['id' => $expiredTask->id]);
        $this->assertDatabaseHas('recurring_tasks', ['id' => $activeTask->id]);
    }

    #[Test]
    public function command_returns_success_when_no_tasks_to_delete(): void
    {
        User::factory()->create();

        $this->artisan('app:archive-expired-recurring-tasks')->assertSuccessful();
    }

    #[Test]
    public function command_output_shows_number_deleted(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(5)
            ->for($user)
            ->create(['end_date' => now()->subDay()->startOfDay()]);

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutput('Archived 5 recurring Tasks.');
    }

    #[Test]
    public function command_output_shows_no_expired_tasks_message(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutput('No expired recurring tasks found to archive.');
    }
}
