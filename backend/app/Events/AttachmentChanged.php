<?php

namespace App\Events;

use App\Models\TaskAttachment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something happened to a task's attachment: uploaded, deleted, virus scan finished, or thumbnail ready.
 * Viewers of the task re-fetch it, so the "latest version only" rule stays in one place (the API).
 */
class AttachmentChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public const UPLOADED = 'uploaded';

    public const DELETED = 'deleted';

    public const SCANNED = 'scanned';

    public const THUMBNAIL_READY = 'thumbnail_ready';

    public int $taskId;

    public array $payload;

    public function __construct(TaskAttachment $attachment, public string $action)
    {
        // Copied up front: after a delete the model can no longer be reloaded.
        $this->taskId = $attachment->task_id;
        $this->payload = [
            'action' => $action,
            'attachment_id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'scan_status' => $attachment->scan_status,
        ];
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("task.{$this->taskId}");
    }

    public function broadcastAs(): string
    {
        return 'attachment.changed';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
