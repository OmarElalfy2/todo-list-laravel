<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_only_their_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->categories()->create(['name' => 'Work']);
        $otherUser->categories()->create(['name' => 'Private']);

        $this->actingAs($user)
            ->getJson('/api/categories')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Work');
    }

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Work',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Work',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_user_cannot_create_duplicate_category(): void
    {
        $user = User::factory()->create();

        $user->categories()->create(['name' => 'Work']);

        $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Work',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_different_users_can_use_same_category_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherUser->categories()->create(['name' => 'Work']);

        $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Work',
            ])
            ->assertStatus(201);

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_user_can_view_category(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Work']);

        $this->actingAs($user)
            ->getJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('name', 'Work');
    }

    public function test_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Work']);

        $this->actingAs($user)
            ->patchJson("/api/categories/{$category->id}", [
                'name' => 'Personal',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Personal',
        ]);
    }

    public function test_user_can_delete_category(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Work']);

        $this->actingAs($user)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_cannot_access_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = $owner->categories()->create(['name' => 'Private']);

        $this->actingAs($otherUser)
            ->getJson("/api/categories/{$category->id}")
            ->assertStatus(403);

        $this->patchJson("/api/categories/{$category->id}", [
            'name' => 'Changed',
        ])->assertStatus(403);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Private',
        ]);
    }

    public function test_deleting_category_deletes_its_tasks(): void
    {
        $user = User::factory()->create();

        $category = $user->categories()->create(['name' => 'Work']);

        $task = $user->tasks()->create([
            'title' => 'Study Laravel',
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }
}
