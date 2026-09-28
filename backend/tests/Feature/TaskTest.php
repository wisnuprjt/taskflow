<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_tasks_requires_authentication(): void
    {
        $this->getJson('/api/tasks')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_tasks_can_be_filtered_and_paginated(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->create(['status' => 'done', 'created_by' => $user->id]);
        Task::factory(2)->create(['status' => 'todo', 'created_by' => $user->id]);

        $this->actingAs($user, 'api')
            ->getJson('/api/tasks?status=done&per_page=2&sort=-due_date')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_task_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/tasks', ['title' => 'Write docs', 'priority' => 'high'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Write docs')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.creator.id', $user->id);

        $this->assertDatabaseHas('tasks', ['title' => 'Write docs', 'created_by' => $user->id]);
    }

    public function test_task_creation_is_validated(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/tasks', ['status' => 'invalid', 'assigned_user_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'assigned_user_id']);
    }

    public function test_unrelated_member_cannot_update_or_delete_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->putJson("/api/tasks/{$task->id}", ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->deleteJson("/api/tasks/{$task->id}")->assertForbidden();
    }

    public function test_creator_can_delete_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->creator, 'api')
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertModelMissing($task);
    }

    public function test_missing_task_returns_json_404(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/tasks/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }
}
