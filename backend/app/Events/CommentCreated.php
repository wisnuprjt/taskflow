<?php

namespace App\Events;

use App\Http\Resources\TaskCommentResource;
use App\Models\TaskComment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Pushes a new comment to everyone currently viewing the task. */
class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public TaskComment $comment) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("task.{$this->comment->task_id}");
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    /** Same shape as the REST response, so the frontend handles both identically. */
    public function broadcastWith(): array
    {
        return ['comment' => TaskCommentResource::make($this->comment->loadMissing('user'))->resolve()];
    }
}
