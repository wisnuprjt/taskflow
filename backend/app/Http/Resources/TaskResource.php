<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toDateString(),
            'assigned_user_id' => $this->assigned_user_id,
            'created_by' => $this->created_by,
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'attachments_count' => $this->whenCounted('attachments'),
            'comments_count' => $this->whenCounted('comments'),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
            // Lets the UI hide actions the current user is not allowed to perform.
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
