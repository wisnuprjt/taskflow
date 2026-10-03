<?php

namespace Tests\Feature;

use App\Http\Controllers\ChunkedUploadController;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChunkedUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(TaskAttachment::DISK);
    }

    /** Sends $content in CHUNK_BYTES slices and returns the upload id. */
    private function sendChunks(Task $task, string $name, string $content, array $skip = []): string
    {
        $init = $this->postJson("/api/tasks/{$task->id}/attachments/chunked", [
            'file_name' => $name,
            'file_size' => strlen($content),
        ])->assertCreated();

        foreach (str_split($content, ChunkedUploadController::CHUNK_BYTES) as $i => $part) {
            if (in_array($i, $skip, true)) {
                continue;
            }
            $this->postJson("/api/uploads/{$init->json('upload_id')}/chunks", [
                'index' => $i,
                'file' => UploadedFile::fake()->createWithContent("{$i}.part", $part),
            ])->assertOk();
        }

        return $init->json('upload_id');
    }

    private function fakePdf(int $bytes): string
    {
        return str_pad("%PDF-1.4\n", $bytes, 'A');
    }

    public function test_large_file_is_uploaded_in_chunks_and_assembled(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');
        $content = $this->fakePdf(11 * 1024 * 1024); // 3 chunks

        $id = $this->sendChunks($task, 'big-report.pdf', $content);

        $response = $this->postJson("/api/uploads/{$id}/complete")
            ->assertCreated()
            ->assertJsonPath('data.file_name', 'big-report.pdf')
            ->assertJsonPath('data.file_size', strlen($content))
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.scan_status', 'clean');

        $attachment = TaskAttachment::findOrFail($response->json('data.id'));
        $this->assertSame($content, Storage::disk(TaskAttachment::DISK)->get($attachment->file_path));
        Storage::disk(TaskAttachment::DISK)->assertMissing("chunks/{$id}");
    }

    public function test_complete_fails_when_chunks_are_missing(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $id = $this->sendChunks($task, 'big-report.pdf', $this->fakePdf(11 * 1024 * 1024), skip: [1]);

        $this->postJson("/api/uploads/{$id}/complete")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Missing chunks: 1');
    }

    public function test_assembled_content_must_match_an_allowed_type(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        // Named .pdf but the bytes are a Windows executable header.
        $id = $this->sendChunks($task, 'fake.pdf', str_pad("MZ\x90\x00", 6 * 1024 * 1024, "\x00"));

        $this->postJson("/api/uploads/{$id}/complete")->assertUnprocessable();
        $this->assertDatabaseCount('task_attachments', 0);
        Storage::disk(TaskAttachment::DISK)->assertMissing("chunks/{$id}");
    }

    public function test_init_rejects_disallowed_type_and_oversized_file(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $this->postJson("/api/tasks/{$task->id}/attachments/chunked", [
            'file_name' => 'setup.exe',
            'file_size' => ChunkedUploadController::MAX_BYTES + 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['file_name', 'file_size']);
    }

    public function test_other_user_cannot_send_chunks_to_an_upload(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');
        $id = $this->postJson("/api/tasks/{$task->id}/attachments/chunked", [
            'file_name' => 'big-report.pdf',
            'file_size' => 1024,
        ])->json('upload_id');

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson("/api/uploads/{$id}/chunks", [
                'index' => 0,
                'file' => UploadedFile::fake()->createWithContent('0.part', 'x'),
            ])->assertForbidden();
    }

    public function test_upload_can_be_aborted(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');
        $id = $this->sendChunks($task, 'big-report.pdf', $this->fakePdf(6 * 1024 * 1024), skip: [1]);

        $this->deleteJson("/api/uploads/{$id}")->assertNoContent();

        Storage::disk(TaskAttachment::DISK)->assertMissing("chunks/{$id}");
        $this->postJson("/api/uploads/{$id}/complete")->assertNotFound();
    }
}
