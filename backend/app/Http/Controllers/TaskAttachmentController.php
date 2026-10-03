<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        $attachment = $task->addAttachment(
            $file->getClientOriginalName(),
            $file->store("attachments/{$task->id}", TaskAttachment::DISK),
            $file->getSize(),
            $file->getMimeType(),
        );

        return TaskAttachmentResource::make($attachment);
    }

    public function versions(TaskAttachment $attachment): AnonymousResourceCollection
    {
        Gate::authorize('view', $attachment->task);

        return TaskAttachmentResource::collection($attachment->versions());
    }

    public function download(TaskAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->task);
        $this->ensureClean($attachment);

        abort_unless(Storage::disk(TaskAttachment::DISK)->exists($attachment->file_path), 404, 'File not found.');

        return Storage::disk(TaskAttachment::DISK)->download($attachment->file_path, $attachment->file_name);
    }

    public function thumbnail(TaskAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->task);
        $this->ensureClean($attachment);

        abort_unless(
            $attachment->thumbnail_path && Storage::disk(TaskAttachment::DISK)->exists($attachment->thumbnail_path),
            404,
            'Thumbnail not available.',
        );

        return Storage::disk(TaskAttachment::DISK)->response($attachment->thumbnail_path);
    }

    public function destroy(TaskAttachment $attachment): Response
    {
        Gate::authorize('update', $attachment->task);

        $attachment->delete();

        return response()->noContent();
    }

    private function ensureClean(TaskAttachment $attachment): void
    {
        abort_if($attachment->scan_status === TaskAttachment::SCAN_PENDING, 423, 'File is still being scanned.');
        abort_if($attachment->scan_status === TaskAttachment::SCAN_INFECTED, 410, 'File was quarantined by the virus scan.');
    }
}
