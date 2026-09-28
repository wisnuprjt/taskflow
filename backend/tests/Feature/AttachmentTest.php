<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(TaskAttachment::DISK);
    }

    public function test_file_can_be_uploaded_downloaded_and_deleted(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $response = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated()->assertJsonPath('data.file_name', 'report.pdf');

        $attachment = TaskAttachment::findOrFail($response->json('data.id'));
        $this->assertNotSame('report.pdf', basename($attachment->file_path));
        Storage::disk(TaskAttachment::DISK)->assertExists($attachment->file_path);

        $this->get("/api/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertDownload('report.pdf');

        $this->deleteJson("/api/attachments/{$attachment->id}")->assertNoContent();
        Storage::disk(TaskAttachment::DISK)->assertMissing($attachment->file_path);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->creator, 'api')
            ->postJson("/api/tasks/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_file_over_size_limit_is_rejected(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->creator, 'api')
            ->postJson("/api/tasks/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('big.pdf', 20481, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_deleting_task_removes_attachment_files(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $id = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('photo.png', 50, 'image/png'),
        ])->assertCreated()->json('data.id');
        $path = TaskAttachment::findOrFail($id)->file_path;

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        Storage::disk(TaskAttachment::DISK)->assertMissing($path);
        $this->assertDatabaseCount('task_attachments', 0);
    }
}
