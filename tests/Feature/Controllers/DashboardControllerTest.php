<?php

namespace Tests\Feature\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_view_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    #[Test]
    public function dashboard_shows_statistics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertViewHas('overdueTasks');
        $response->assertViewHas('completedToday');
        $response->assertViewHas('completedLastSevenDays');
        $response->assertViewHas('totalTasks');
        $response->assertViewHas('overdueTasksList');
        $response->assertViewHas('todayTasksList');
    }

    #[Test]
    public function dashboard_counts_total_tasks_correctly(): void
    {
        $user = User::factory()->create();
        Task::factory(5)->for($user)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(5, $response->viewData('totalTasks'));
    }

    #[Test]
    public function dashboard_counts_overdue_tasks_correctly(): void
    {
        $user = User::factory()->create();
        $pastDate = now()->subDays(5)->startOfDay();

        Task::factory(3)->for($user)->create(['task_date' => $pastDate]);
        Task::factory(2)->for($user)->create(['task_date' => now()->addDays(5)]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(3, $response->viewData('overdueTasks'));
    }

    #[Test]
    public function dashboard_does_not_count_completed_tasks_as_overdue(): void
    {
        $user = User::factory()->create();
        $pastDate = now()->subDays(5)->startOfDay();

        Task::factory(2)->for($user)->completed()->create(['task_date' => $pastDate]);
        Task::factory(1)->for($user)->create(['task_date' => $pastDate]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(1, $response->viewData('overdueTasks'));
    }

    #[Test]
    public function dashboard_counts_completed_today(): void
    {
        $user = User::factory()->create();
        $today = now()->startOfDay();

        Task::factory(3)->for($user)->completed()->create(['completed_at' => $today->addHours(8)]);
        Task::factory(2)->for($user)->completed()->create(['completed_at' => now()->subDays(1)]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(3, $response->viewData('completedToday'));
    }

    #[Test]
    public function dashboard_counts_completed_last_seven_days(): void
    {
        $user = User::factory()->create();
        $sevenDaysAgo = now()->subDays(7)->startOfDay();

        Task::factory(5)->for($user)->completed()->create(['completed_at' => now()->subDays(3)->addHours(10)]);
        Task::factory(2)->for($user)->completed()->create(['completed_at' => now()->subDays(10)]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(5, $response->viewData('completedLastSevenDays'));
    }

    #[Test]
    public function dashboard_shows_overdue_tasks_list(): void
    {
        $user = User::factory()->create();
        $pastDate = now()->subDays(5)->startOfDay();

        $overdueTasks = Task::factory(3)->for($user)->create(['task_date' => $pastDate]);
        Task::factory(2)->for($user)->create(['task_date' => now()->addDays(5)]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $overdueList = $response->viewData('overdueTasksList');
        $this->assertCount(3, $overdueList);
    }

    #[Test]
    public function dashboard_shows_todays_tasks_list(): void
    {
        $user = User::factory()->create();
        $today = now()->startOfDay();

        Task::factory(2)->for($user)->create(['task_date' => $today->addHours(8)]);
        Task::factory(3)->for($user)->create(['task_date' => now()->addDays(1)]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $todayList = $response->viewData('todayTasksList');
        $this->assertCount(2, $todayList);
    }

    #[Test]
    public function dashboard_shows_overdue_tasks_sorted_by_date(): void
    {
        $user = User::factory()->create();
        $olderDate = now()->subDays(10);
        $newerDate = now()->subDays(3);

        Task::factory()->for($user)->create(['task_date' => $newerDate, 'title' => 'Recent Overdue']);
        Task::factory()->for($user)->create(['task_date' => $olderDate, 'title' => 'Old Overdue']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $overdueList = $response->viewData('overdueTasksList');
        $this->assertSame('Old Overdue', $overdueList[0]['title']);
        $this->assertSame('Recent Overdue', $overdueList[1]['title']);
    }

    #[Test]
    public function dashboard_shows_todays_tasks_with_incomplete_first(): void
    {
        $user = User::factory()->create();
        $today = now()->startOfDay();

        Task::factory()->for($user)->completed()->create(['task_date' => $today, 'title' => 'Completed Task']);
        Task::factory()->for($user)->create(['task_date' => $today, 'title' => 'Incomplete Task']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $todayList = $response->viewData('todayTasksList');
        $this->assertSame('Incomplete Task', $todayList[0]['title']);
        $this->assertSame('Completed Task', $todayList[1]['title']);
    }

    #[Test]
    public function dashboard_shows_only_current_user_tasks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory(5)->for($user1)->create();
        Task::factory(10)->for($user2)->create();

        $response = $this->actingAs($user1)->get(route('dashboard'));

        $this->assertSame(5, $response->viewData('totalTasks'));
    }

    #[Test]
    public function dashboard_with_no_tasks_shows_zeros(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(0, $response->viewData('overdueTasks'));
        $this->assertSame(0, $response->viewData('completedToday'));
        $this->assertSame(0, $response->viewData('completedLastSevenDays'));
        $this->assertSame(0, $response->viewData('totalTasks'));
    }

    #[Test]
    public function dashboard_lists_have_category_info(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Work']);
        $task = Task::factory()->for($user)->for($category)->create([
            'task_date' => now()->subDays(2)->startOfDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $overdueList = $response->viewData('overdueTasksList');
        $this->assertNotNull($overdueList[0]['category']);
    }
}
