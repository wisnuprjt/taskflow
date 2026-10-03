<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Large files (above the 20 MB single-request limit) are sent in 5 MB chunks:
 * init -> chunk (repeat, retry-safe) -> complete. Upload state lives in the cache for a day.
 */
class ChunkedUploadController extends Controller
{
    /** Stays well under PHP's upload_max_filesize, so each chunk is a normal small request. */
    public const CHUNK_BYTES = 5 * 1024 * 1024;

    public const MAX_BYTES = 500 * 1024 * 1024;

    public function init(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $data = $request->validate([
            'file_name' => ['required', 'string', 'max:255', 'regex:/\.('.implode('|', StoreAttachmentRequest::MIMES).')$/i'],
            'file_size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_BYTES],
        ], ['file_name.regex' => 'This file type is not allowed.']);

        $id = (string) Str::uuid();
        $upload = [
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'file_name' => $data['file_name'],
            'file_size' => (int) $data['file_size'],
            'total_chunks' => (int) ceil($data['file_size'] / self::CHUNK_BYTES),
        ];
        Cache::put($this->key($id), $upload, now()->addDay());

        return response()->json([
            'upload_id' => $id,
            'chunk_size' => self::CHUNK_BYTES,
            'total_chunks' => $upload['total_chunks'],
        ], 201);
    }

    public function chunk(Request $request, string $uploadId): JsonResponse
    {
        $upload = $this->find($request, $uploadId);

        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0', 'max:'.($upload['total_chunks'] - 1)],
            'file' => ['required', 'file', 'max:'.(self::CHUNK_BYTES / 1024)],
        ]);

        // Fixed name per index, so a retried chunk simply overwrites the previous attempt.
        $request->file('file')->storeAs($this->dir($uploadId), "{$data['index']}.part", TaskAttachment::DISK);

        return response()->json(['received' => (int) $data['index']]);
    }

    public function complete(Request $request, string $uploadId): TaskAttachmentResource
    {
        $upload = $this->find($request, $uploadId);
        $task = Task::findOrFail($upload['task_id']);
        Gate::authorize('update', $task);

        $disk = Storage::disk(TaskAttachment::DISK);
        $dir = $this->dir($uploadId);

        $missing = array_values(array_filter(
            range(0, $upload['total_chunks'] - 1),
            fn (int $i) => ! $disk->exists("{$dir}/{$i}.part"),
        ));
        abort_if($missing !== [], 422, 'Missing chunks: '.implode(', ', $missing));

        // Stream the parts into one file so memory use stays flat regardless of file size.
        $assembled = $disk->path("{$dir}/assembled");
        $out = fopen($assembled, 'wb');
        for ($i = 0; $i < $upload['total_chunks']; $i++) {
            $in = fopen($disk->path("{$dir}/{$i}.part"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        // Same rule as single uploads: the type is judged by the content, not the client's file name.
        $file = new File($assembled);
        $extension = $file->guessExtension();
        $error = match (true) {
            $file->getSize() !== $upload['file_size'] => 'Assembled file size does not match.',
            ! in_array($extension, StoreAttachmentRequest::MIMES, true) => 'This file type is not allowed.',
            default => null,
        };
        if ($error !== null) {
            $this->discard($uploadId);
            abort(422, $error);
        }

        $mime = $file->getMimeType();
        $path = "attachments/{$task->id}/".Str::random(40).".{$extension}";
        $disk->move("{$dir}/assembled", $path);
        $this->discard($uploadId);

        return TaskAttachmentResource::make($task->addAttachment($upload['file_name'], $path, $upload['file_size'], $mime));
    }

    public function abort(Request $request, string $uploadId): Response
    {
        $this->find($request, $uploadId);
        $this->discard($uploadId);

        return response()->noContent();
    }

    /** @return array{task_id: int, user_id: int, file_name: string, file_size: int, total_chunks: int} */
    private function find(Request $request, string $uploadId): array
    {
        $upload = Cache::get($this->key($uploadId));

        abort_unless($upload, 404, 'Upload session not found or expired.');
        abort_unless($upload['user_id'] === $request->user()->id, 403);

        return $upload;
    }

    private function discard(string $uploadId): void
    {
        Storage::disk(TaskAttachment::DISK)->deleteDirectory($this->dir($uploadId));
        Cache::forget($this->key($uploadId));
    }

    private function key(string $uploadId): string
    {
        return "chunked-upload:{$uploadId}";
    }

    private function dir(string $uploadId): string
    {
        return "chunks/{$uploadId}";
    }
}
