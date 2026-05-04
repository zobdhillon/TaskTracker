# Testing Conventions & Patterns

This document outlines the testing conventions and patterns used across the TaskTracker application. All future tests should follow these approaches to maintain consistency and quality.

## Table of Contents

1. [Test Organization](#test-organization)
2. [Test Structure](#test-structure)
3. [Feature Test Patterns](#feature-test-patterns)
4. [Unit Test Patterns](#unit-test-patterns)
5. [Data Providers](#data-providers)
6. [Authorization & Policy Testing](#authorization--policy-testing)
7. [Edge Cases & Validation](#edge-cases--validation)
8. [Factory Usage](#factory-usage)
9. [Naming Conventions](#naming-conventions)
10. [Running Tests](#running-tests)

---

## Test Organization

### Directory Structure

Tests are organized by type and feature:

```
tests/
├── Feature/
│   ├── Controllers/          # HTTP controller tests
│   └── Console/
│       └── Commands/         # Artisan command tests
└── Unit/
    ├── Actions/              # Action class tests
    │   ├── Auth/
    │   ├── Category/
    │   └── Task/
    ├── Services/             # Service class tests
    ├── Enum/                 # Enum tests
    └── Models/               # Model tests (when needed)
```

**Rationale**: Feature tests verify end-to-end functionality through HTTP requests or command execution. Unit tests isolate and test individual classes like Actions and Services. This separation allows for comprehensive coverage at appropriate abstraction levels.

---

## Test Structure

### PHPUnit Attributes

All tests use PHPUnit 12 attributes instead of docblock annotations:

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

#[Test]
public function user_can_create_task(): void
{
    // Test implementation
}

#[Test]
#[DataProvider('taskDataProvider')]
public function task_validation_works(array $data): void
{
    // Parameterized test
}
```

**Benefits**: Attributes are more maintainable, type-safe, and clearly indicate test intent.

### Test Method Naming

Test method names use `snake_case` and clearly describe what is being tested:

```php
// ✓ Good
#[Test]
public function user_can_view_own_tasks(): void

#[Test]
public function guest_cannot_create_task(): void

#[Test]
public function task_creation_fails_with_invalid_data(): void

// ✗ Avoid
#[Test]
public function test1(): void

#[Test]
public function testCreate(): void
```

### Arrange-Act-Assert Pattern

All tests follow the AAA pattern with clear sections:

```php
#[Test]
public function authenticated_user_can_create_task(): void
{
    // Arrange
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    // Act
    $response = $this->actingAs($user)->post(route('tasks.store'), [
        'title' => 'Test Task',
        'category_id' => $category->uuid,
        'task_date' => now()->toDateString(),
    ]);

    // Assert
    $response->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Test Task',
    ]);
}
```

---

## Feature Test Patterns

### Test Traits

Feature tests use these Laravel testing traits:

```php
use RefreshDatabase;     // Refreshes database between tests
use WithFaker;          // Provides $this->faker for test data
```

### Authentication Testing

Always test both authenticated and guest scenarios:

```php
#[Test]
public function guest_cannot_view_tasks(): void
{
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
}

#[Test]
public function authenticated_user_can_view_tasks(): void
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('tasks.index'));
    $response->assertOk();
}
```

### Response Assertions

Use explicit Laravel assertions:

```php
$response->assertOk();                           // Status 200
$response->assertRedirect(route('tasks.index'));  // Status 302 with location
$response->assertForbidden();                    // Status 403
$response->assertViewIs('tasks.index');          // Check view name
$response->assertViewHas('tasks');               // Check view data
$response->assertSessionHas('success', 'Task created successfully.');
$response->assertInvalid('title');               // Check validation errors
$response->assertNoContent();                    // Status 204
```

### Testing Redirects with Session Messages

```php
#[Test]
public function category_update_shows_success_message(): void
{
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $response = $this->actingAs($user)->put(
        route('categories.update', $category),
        ['name' => 'Updated']
    );

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success', 'Category updated successfully.');
}
```

---

## Unit Test Patterns

### Unit Test Structure

Unit tests instantiate classes via the service container for dependency injection:

```php
#[Test]
public function category_is_created_with_correct_attributes(): void
{
    $user = User::factory()->create();
    $createCategory = app(CreateCategory::class);

    $category = $createCategory->execute(['name' => 'Work'], $user);

    $this->assertEquals('Work', $category->name);
    $this->assertEquals($user->id, $category->user_id);
}
```

### Mocking Dependencies (When Applicable)

For services with external dependencies, mock only what's necessary:

```php
#[Test]
public function registered_event_is_dispatched(): void
{
    Event::fake();
    $registerUser = app(RegisterUser::class);

    $registerUser->execute([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    Event::assertDispatched(Registered::class);
}
```

### Exception Testing

Test that exceptions are thrown for invalid conditions:

```php
#[Test]
public function throws_validation_exception_for_nonexistent_category(): void
{
    $user = User::factory()->create();
    $resolveCategory = app(ResolveCategory::class);

    $this->expectException(ValidationException::class);
    $resolveCategory->execute('nonexistent-uuid', $user);
}
```

---

## Data Providers

### Static Data Providers

Use data providers for testing multiple scenarios with different inputs:

```php
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
```

**Benefits**: Reduces code duplication, makes test variations obvious, and improves maintainability.

---

## Authorization & Policy Testing

### Testing Permission Checks

Always test that users cannot access resources they don't own:

```php
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
public function authenticated_user_can_delete_own_task(): void
{
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

    $response->assertNoContent();
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
}
```

### Testing with Policies

Policies are tested through controller actions, not directly:

```php
// Policy test pattern (via controller)
#[Test]
public function user_cannot_update_another_users_category(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->for($owner)->create();

    $this->actingAs($otherUser)->put(
        route('categories.update', $category),
        ['name' => 'Hacked']
    )->assertForbidden();
}
```

---

## Edge Cases & Validation

### Testing Null/Optional Fields

```php
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

    $this->assertDatabaseHas('tasks', [
        'title' => 'Task without description',
        'description' => null,
    ]);
}

#[Test]
public function resolve_category_returns_null_when_uuid_is_null(): void
{
    $user = User::factory()->create();
    $resolveCategory = app(ResolveCategory::class);

    $categoryId = $resolveCategory->execute(null, $user);

    $this->assertNull($categoryId);
}
```

### Testing Relationships & Cascades

```php
#[Test]
public function deleting_category_deletes_associated_tasks(): void
{
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Task::factory(3)->for($user)->for($category)->create();

    $this->actingAs($user)->delete(route('categories.destroy', $category));

    $this->assertDatabaseCount('tasks', 0);
}
```

### Testing Filtering & Pagination

```php
#[Test]
public function task_index_can_filter_by_category(): void
{
    $user = User::factory()->create();
    $category1 = Category::factory()->for($user)->create();
    $category2 = Category::factory()->for($user)->create();

    Task::factory(2)->for($user)->for($category1)->create();
    Task::factory(3)->for($user)->for($category2)->create();

    $response = $this->actingAs($user)->get(
        route('tasks.index'),
        ['category_id' => $category1->uuid]
    );

    $this->assertCount(2, $response->viewData('tasks'));
}

#[Test]
public function categories_index_is_paginated(): void
{
    $user = User::factory()->create();
    Category::factory(25)->for($user)->create();

    $response = $this->actingAs($user)->get(route('categories.index'));

    $this->assertCount(15, $response->viewData('categories'));
    $this->assertNotNull($response->viewData('links'));
}
```

---

## Factory Usage

### Using Factory States

The application provides custom factory states for common scenarios:

```php
// Task factory states
Task::factory()->completed()->create();           // Task with completed_at = now()
Task::factory()->today()->create();               // Task with task_date = today()
Task::factory()->overdue()->create();             // Task with past date
Task::factory()->withoutcategory()->create();    // Task with category_id = null

// RecurringTask factory states
RecurringTask::factory()->daily()->create();
RecurringTask::factory()->weekdays()->create();

// Category factory
Category::factory()->for($user)->create();

// User factory
User::factory()->unverified()->create();          // User with email_verified_at = null
User::factory()->create(['email_verified_at' => now()]);  // Verified user
```

**Always check factories before manually creating models** to avoid duplication.

### Factory Relationships

```php
#[Test]
public function tasks_belong_to_user(): void
{
    $user = User::factory()->create();

    // Creates task with user_id = $user->id
    $task = Task::factory()->for($user)->create();

    $this->assertEquals($user->id, $task->user_id);
}

#[Test]
public function tasks_belong_to_category(): void
{
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $task = Task::factory()->for($user)->for($category)->create();

    $this->assertEquals($category->id, $task->category_id);
}
```

---

## Naming Conventions

### Test File Naming

Test files are named after the class they test with `Test` suffix:

```
Controllers/TaskControllerTest.php       → TaskController
Actions/Auth/RegisterUserActionTest.php  → RegisterUser
Services/CategoryCacheServiceTest.php    → CategoryCacheService
Enum/TaskStatusTest.php                  → TaskStatus
Console/Commands/GenerateRecurringTasksTest.php → GenerateRecurringTasks
```

### Test Class Naming

Test classes match filename and extend `TestCase`:

```php
class TaskControllerTest extends TestCase
class RegisterUserActionTest extends TestCase
class CategoryCacheServiceTest extends TestCase
```

### Test Method Naming Convention

Follow the pattern: `[subject]_[condition]_[expected_outcome]`

```php
// ✓ Good patterns
public function user_can_create_task(): void
public function guest_cannot_view_tasks(): void
public function task_creation_fails_with_invalid_data(): void
public function user_cannot_delete_another_users_task(): void
public function category_is_cached_when_retrieved(): void

// ✗ Avoid
public function userCreateTest(): void
public function testCreate(): void
public function test_create_should_work(): void
```

---

## Running Tests

### Run All Tests

```bash
php artisan test --compact
```

### Run Specific Test File

```bash
php artisan test tests/Feature/Controllers/TaskControllerTest.php
```

### Run Tests in Specific Directory

```bash
php artisan test tests/Feature/Controllers
php artisan test tests/Unit/Actions
```

### Run Tests Matching Filter

```bash
php artisan test --filter=task_creation
php artisan test --filter=authorization
```

### Run with Detailed Output

```bash
php artisan test
```

### Run Tests for Specific Method

```bash
php artisan test --filter=user_can_create_task
```

---

## Test Coverage by Component

### Controllers

| Controller                  | Test File                           | Coverage                                          |
| --------------------------- | ----------------------------------- | ------------------------------------------------- |
| TaskController              | TaskControllerTest.php              | CRUD, filtering, authorization, toggle completion |
| CategoryController          | CategoryControllerTest.php          | CRUD, authorization, cascading deletes            |
| RecurringTaskController     | RecurringTaskControllerTest.php     | CRUD, frequencies, authorization                  |
| DashboardController         | DashboardControllerTest.php         | Statistics, filtering, sorting                    |
| AuthController              | AuthControllerTest.php              | Registration, login, password reset               |
| EmailVerificationController | EmailVerificationControllerTest.php | Email verification flow                           |

### Commands

| Command                      | Test File                            | Coverage                                    |
| ---------------------------- | ------------------------------------ | ------------------------------------------- |
| GenerateRecurringTasks       | GenerateRecurringTasksTest.php       | Daily, weekday, weekly, monthly frequencies |
| ArchiveExpiredRecurringTasks | ArchiveExpiredRecurringTasksTest.php | Expired task deletion                       |

### Actions

| Action          | Test File                  | Coverage                       |
| --------------- | -------------------------- | ------------------------------ |
| RegisterUser    | RegisterUserActionTest.php | User creation, event dispatch  |
| CreateCategory  | CategoryActionsTest.php    | Category creation              |
| GetCategories   | CategoryActionsTest.php    | Retrieval, sorting, caching    |
| ResolveCategory | CategoryActionsTest.php    | UUID resolution, authorization |
| CreateTask      | CreateTaskActionTest.php   | Task creation, validation      |

### Services

| Service              | Test File                    | Coverage                           |
| -------------------- | ---------------------------- | ---------------------------------- |
| CategoryCacheService | CategoryCacheServiceTest.php | Cache storage, retrieval, clearing |

### Enums

| Enum          | Test File             | Coverage                        |
| ------------- | --------------------- | ------------------------------- |
| TaskStatus    | TaskStatusTest.php    | Enum values, creation           |
| TaskFrequency | TaskFrequencyTest.php | Config building for frequencies |

---

## Best Practices Summary

1. **Test names describe intent**: Use clear, descriptive names that explain what the test verifies
2. **Follow AAA pattern**: Arrange, Act, Assert clearly separated
3. **Test both success and failure**: Include happy path and error scenarios
4. **Use factory states**: Leverage existing factory states before creating manual setup
5. **Test authorization**: Always verify users can only access their own resources
6. **Test validation**: Provide invalid data and verify proper error handling
7. **Use data providers**: For multiple test cases with different inputs
8. **Keep tests focused**: Each test should verify one specific behavior
9. **Use appropriate assertions**: Choose specific assertions over generic assertTrue/False
10. **Run tests frequently**: Run tests after making changes to catch regressions early

---

## Adding New Tests

When adding a new feature:

1. Identify the component type (Controller, Action, Service, Command)
2. Create test file in appropriate directory using naming conventions
3. Add Feature or Unit traits as needed
4. Write tests following AAA pattern
5. Include happy path, failure path, and edge cases
6. Run tests to ensure they pass: `php artisan test --filter=your_test_name`
7. Update this documentation if new patterns are introduced

---

**Last Updated**: May 4, 2026
**Version**: 1.0
**Framework**: Laravel 13.3 with PHPUnit 12
