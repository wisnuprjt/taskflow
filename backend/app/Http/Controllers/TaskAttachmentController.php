<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Task $task): TaskAttachmentResource
    {
        Gate::authorize('update', $task);

        $file = $request->file('file');

        // store() uses a random hashed filename; the original name is kept only in the DB.
        $attachment = $task->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store("attachments/{$task->id}", TaskAttachment::DISK),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return TaskAttachmentResource::make($attachment);
    }

    public function download(TaskAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->task);

        abort_unless(Storage::disk(TaskAttachment::DISK)->exists($attachment->file_path), 404, 'File not found.');

        return Storage::disk(TaskAttachment::DISK)->download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(TaskAttachment $attachment): Response
    {
        Gate::authorize('update', $attachment->task);

        $attachment->delete();

        return response()->noContent();
    }
}
