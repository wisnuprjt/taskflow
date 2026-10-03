<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A task was created, updated or deleted. Deliberately carries no task data: the API response
 * includes per-user permissions (`can`), so each client re-fetches the task with its own token.
 */
class TaskChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public int $taskId;

    public string $title;

    public function __construct(Task $task, public string $action, public User $actor)
    {
        // Copied up front: after a delete the model can no longer be reloaded.
        $this->taskId = $task->id;
        $this->title = $task->title;
    }

    /** @return PrivateChannel[] */
    public function broadcastOn(): array
    {
        // `tasks` refreshes the list page, `task.{id}` the detail page of this task.
        return [new PrivateChannel('tasks'), new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastAs(): string
    {
        return "task.{$this->action}";
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'title' => $this->title,
            'actor' => ['id' => $this->actor->id, 'name' => $this->actor->name],
        ];
    }
}
