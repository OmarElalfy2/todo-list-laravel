<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_only_their_tasks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->tasks()->create(['title' => 'My Task']);
        $otherUser->tasks()->create(['title' => 'Private Task']);

        $this->actingAs($user)
            ->getJson('/api/tasks')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'My Task');
    }

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create();

        $category = $user->categories()->create([
            'name' => 'University',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'Study Laravel',
                'category_id' => $category->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('category.name', 'University');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Study Laravel',
            'category_id' => $category->id,
        ]);
    }

    public function test_task_title_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/tasks', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_user_cannot_use_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = $otherUser->categories()->create([
            'name' => 'Private',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'Study Laravel',
                'category_id' => $category->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_user_can_view_task(): void
    {
        $user = User::factory()->create();

        $task = $user->tasks()->create([
            'title' => 'Study Laravel',
        ]);

        $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}")
            ->assertStatus(200)
            ->assertJsonPath('title', 'Study Laravel');
    }

    public function test_user_can_update_task(): void
    {
        $user = User::factory()->create();

        $task = $user->tasks()->create([
            'title' => 'Study Laravel',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/tasks/{$task->id}", [
                'is_completed' => true,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Study Laravel',
            'is_completed' => true,
        ]);
    }

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->create();

        $task = $user->tasks()->create([
            'title' => 'Delete Me',
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_access_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = $owner->tasks()->create([
            'title' => 'Private Task',
        ]);

        $this->actingAs($otherUser)
            ->getJson("/api/tasks/{$task->id}")
            ->assertStatus(403);

        $this->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Changed',
        ])->assertStatus(403);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Private Task',
        ]);
    }
}
