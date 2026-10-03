<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\AttachmentChanged;
use App\Events\CommentCreated;
use App\Events\RoleChanged;
use App\Events\TaskChanged;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealtimeEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_comment_is_broadcast_on_task_channel(): void
    {
        Event::fake([CommentCreated::class]);
        $task = Task::factory()->create();

        $id = $this->actingAs($task->creator, 'api')
            ->postJson("/api/tasks/{$task->id}/comments", ['comment' => 'Hello team'])
            ->assertCreated()
            ->json('data.id');

        Event::assertDispatched(CommentCreated::class, function (CommentCreated $event) use ($task, $id) {
            return $event->broadcastOn()->name === "private-task.{$task->id}"
                && $event->broadcastWith()['comment']['id'] === $id
                && $event->broadcastWith()['comment']['user']['id'] === $task->creator->id;
        });
    }

    public function test_task_create_update_delete_are_broadcast(): void
    {
        Event::fake([TaskChanged::class]);
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $newId = $this->postJson('/api/tasks', ['title' => 'Realtime task', 'status' => 'todo', 'priority' => 'low'])
            ->assertCreated()->json('data.id');
        $this->putJson("/api/tasks/{$task->id}", ['title' => 'Renamed'])->assertOk();
        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $sent = fn (string $action, int $id) => Event::assertDispatched(TaskChanged::class, fn (TaskChanged $e) => $e->broadcastAs() === "task.{$action}"
            && $e->broadcastWith()['task_id'] === $id
            && $e->broadcastWith()['actor']['id'] === $task->creator->id
            && array_map(fn ($c) => $c->name, $e->broadcastOn()) === ['private-tasks', "private-task.{$id}"]);

        $sent('created', $newId);
        $sent('updated', $task->id);
        $sent('deleted', $task->id);
    }

    public function test_attachment_lifecycle_is_broadcast_on_task_channel(): void
    {
        Storage::fake(TaskAttachment::DISK);
        Event::fake([AttachmentChanged::class]);
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        // Sync queue in tests: upload -> scan -> thumbnail all run within this request.
        $id = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->image('photo.png', 640, 480),
        ])->assertCreated()->json('data.id');
        $this->deleteJson("/api/attachments/{$id}")->assertNoContent();

        $actions = Event::dispatched(AttachmentChanged::class)
            ->map(fn (array $args) => $args[0])
            ->each(fn (AttachmentChanged $e) => $this->assertSame("private-task.{$task->id}", $e->broadcastOn()->name))
            ->map(fn (AttachmentChanged $e) => $e->action)
            ->all();

        $this->assertSame(['uploaded', 'scanned', 'thumbnail_ready', 'deleted'], $actions);
    }

    public function test_role_command_changes_role_and_notifies_user(): void
    {
        Event::fake([RoleChanged::class]);
        $user = User::factory()->create(['role' => 'member']);

        $this->artisan('user:role', ['email' => $user->email, 'role' => 'admin'])->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
        Event::assertDispatched(RoleChanged::class, fn (RoleChanged $e) => $e->broadcastOn()->name === "private-App.Models.User.{$user->id}"
            && $e->broadcastWith()['user']['role'] === UserRole::Admin);
    }

    public function test_role_command_rejects_unknown_role_and_other_updates_do_not_broadcast(): void
    {
        Event::fake([RoleChanged::class]);
        $user = User::factory()->create(['role' => 'member']);

        $this->artisan('user:role', ['email' => $user->email, 'role' => 'superuser'])->assertFailed();
        $user->update(['name' => 'Renamed']);

        Event::assertNotDispatched(RoleChanged::class);
    }
}
