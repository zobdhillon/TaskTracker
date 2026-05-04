<?php

namespace Tests\Unit\Enum;

use App\Enums\TaskStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    #[Test]
    public function completed_status_has_correct_value(): void
    {
        $this->assertEquals('completed', TaskStatus::Completed->value);
    }

    #[Test]
    public function incomplete_status_has_correct_value(): void
    {
        $this->assertEquals('incomplete', TaskStatus::Incomplete->value);
    }

    #[Test]
    public function status_enum_has_two_cases(): void
    {
        $cases = TaskStatus::cases();
        $this->assertCount(2, $cases);
    }

    #[Test]
    public function status_enum_contains_completed(): void
    {
        $this->assertContains(TaskStatus::Completed, TaskStatus::cases());
    }

    #[Test]
    public function status_enum_contains_incomplete(): void
    {
        $this->assertContains(TaskStatus::Incomplete, TaskStatus::cases());
    }

    #[Test]
    public function can_create_status_from_value(): void
    {
        $completed = TaskStatus::from('completed');
        $this->assertEquals(TaskStatus::Completed, $completed);

        $incomplete = TaskStatus::from('incomplete');
        $this->assertEquals(TaskStatus::Incomplete, $incomplete);
    }

    #[Test]
    public function cannot_create_status_from_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        TaskStatus::from('invalid');
    }

    #[Test]
    public function can_try_status_from_value(): void
    {
        $completed = TaskStatus::tryFrom('completed');
        $this->assertEquals(TaskStatus::Completed, $completed);

        $invalid = TaskStatus::tryFrom('invalid');
        $this->assertNull($invalid);
    }

    #[Test]
    public function status_names_match_case_names(): void
    {
        $this->assertEquals('Completed', TaskStatus::Completed->name);
        $this->assertEquals('Incomplete', TaskStatus::Incomplete->name);
    }
}
