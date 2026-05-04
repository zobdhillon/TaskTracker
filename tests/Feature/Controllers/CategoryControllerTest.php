<?php

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function guest_cannot_view_categories(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_own_categories(): void
    {
        $user = User::factory()->create();
        Category::factory(3)->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertViewIs('categories.index');
        $response->assertViewHas('categories');
        $this->assertCount(3, $response->viewData('categories'));
    }

    #[Test]
    public function user_can_only_view_own_categories(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Category::factory(2)->for($user1)->create();
        Category::factory(3)->for($user2)->create();

        $response = $this->actingAs($user1)->get(route('categories.index'));

        $this->assertCount(2, $response->viewData('categories'));
    }

    #[Test]
    public function categories_are_sorted_by_latest(): void
    {
        $user = User::factory()->create();

        $oldCategory = Category::factory()->for($user)->create();
        $this->travel(1)->day();
        $newCategory = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertViewHas('categories');

        $categories = $response->viewData('categories');

        $this->assertEquals(
            $newCategory->uuid,
            $categories[0]['id']
        );
    }

    #[Test]
    public function guest_cannot_access_create_category_form(): void
    {
        $this->get(route('categories.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_category_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('categories.create'));

        $response->assertOk();
        $response->assertViewIs('categories.create');
    }

    #[Test]
    public function guest_cannot_create_category(): void
    {
        $this->post(route('categories.store'), ['name' => 'Work'])->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_create_category(): void
    {
        $user = User::factory()->create();
        $categoryName = 'Work';

        $response = $this->actingAs($user)->post(route('categories.store'), ['name' => $categoryName]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success', 'Category created successfully.');
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => $categoryName,
        ]);
    }

    public static function invalidCategoryDataProvider(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'name too long' => [['name' => str_repeat('a', 256)], 'name'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCategoryDataProvider')]
    public function category_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertDatabaseCount('categories', 0);
    }

    #[Test]
    public function guest_cannot_access_edit_category_form(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_access_another_users_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get(route('categories.edit', $category))->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_view_edit_category_form(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.edit', $category));

        $response->assertOk();
        $response->assertViewIs('categories.edit');
        $response->assertViewHas('category', function ($data) use ($category) {
            return $data['id'] === $category->uuid;
        });
    }

    #[Test]
    public function guest_cannot_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->put(route('categories.update', $category), ['name' => 'Updated'])->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_update_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $this->actingAs($otherUser)->put(route('categories.update', $category), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_update_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $newName = 'Updated Category';

        $response = $this->actingAs($user)->put(route('categories.update', $category), ['name' => $newName]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success', 'Category updated successfully.');
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $newName,
        ]);
    }

    #[Test]
    public function category_update_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('categories.update', $category), ['name' => '']);

        $response->assertInvalid('name');
    }

    #[Test]
    public function guest_cannot_delete_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_cannot_delete_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $this->actingAs($otherUser)->delete(route('categories.destroy', $category))->assertForbidden();
    }

    #[Test]
    public function authenticated_user_can_delete_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function deleting_category_deletes_associated_tasks(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Task::factory(3)->for($user)->for($category)->create();

        $this->actingAs($user)->delete(route('categories.destroy', $category));

        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function categories_index_is_paginated(): void
    {
        $user = User::factory()->create();
        Category::factory(25)->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $this->assertCount(15, $response->viewData('categories'));
        $this->assertNotNull($response->viewData('links'));
    }
}
