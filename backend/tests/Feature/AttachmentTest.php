<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Jobs\ScanAttachment;
use App\Models\TaskAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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

    public function test_image_upload_generates_thumbnail(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $response = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->image('photo.png', 1200, 800),
        ])->assertCreated();

        $attachment = TaskAttachment::findOrFail($response->json('data.id'));
        Storage::disk(TaskAttachment::DISK)->assertExists($attachment->thumbnail_path);
        [$width] = getimagesizefromstring(Storage::disk(TaskAttachment::DISK)->get($attachment->thumbnail_path));
        $this->assertSame(320, $width);

        $this->getJson("/api/attachments/{$attachment->id}/thumbnail")->assertOk();

        $this->deleteJson("/api/attachments/{$attachment->id}")->assertNoContent();
        Storage::disk(TaskAttachment::DISK)->assertMissing($attachment->thumbnail_path);
    }

    public function test_non_image_upload_has_no_thumbnail(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $id = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.thumbnail_url', null)->json('data.id');

        $this->getJson("/api/attachments/{$id}/thumbnail")->assertNotFound();
    }

    public function test_file_with_virus_signature_is_quarantined(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $id = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello '.ScanAttachment::TEST_SIGNATURE),
        ])->assertCreated()->assertJsonPath('data.scan_status', 'infected')->json('data.id');

        Storage::disk(TaskAttachment::DISK)->assertMissing(TaskAttachment::findOrFail($id)->file_path);
        $this->getJson("/api/attachments/{$id}/download")->assertStatus(410);
    }

    public function test_disguised_double_extension_is_quarantined(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('invoice.exe.pdf', 10, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.scan_status', 'infected');
    }

    public function test_download_is_blocked_until_scan_finishes(): void
    {
        Queue::fake();
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $id = $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.scan_status', 'pending')->json('data.id');

        Queue::assertPushed(ScanAttachment::class);
        $this->getJson("/api/attachments/{$id}/download")->assertStatus(423);
    }

    public function test_reuploading_same_file_name_creates_new_version(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        $upload = fn () => $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $v1 = $upload()->assertJsonPath('data.version', 1)->json('data.id');
        $v2 = $upload()->assertJsonPath('data.version', 2)->json('data.id');

        // Task detail lists only the newest version; history is on /versions.
        $this->getJson("/api/tasks/{$task->id}")
            ->assertJsonPath('data.attachments_count', 1)
            ->assertJsonCount(1, 'data.attachments')
            ->assertJsonPath('data.attachments.0.id', $v2);

        $this->getJson("/api/attachments/{$v1}/versions")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.1.version', 1);
        $this->get("/api/attachments/{$v1}/download")->assertOk();

        // Removing the newest version promotes the previous one back to latest.
        $this->deleteJson("/api/attachments/{$v2}")->assertNoContent();
        $this->getJson("/api/tasks/{$task->id}")->assertJsonPath('data.attachments.0.id', $v1);
    }

    public function test_signature_split_across_scan_blocks_is_detected(): void
    {
        $task = Task::factory()->create();
        $this->actingAs($task->creator, 'api');

        // The scanner reads 1 MB blocks; place the signature so it straddles the first boundary.
        $content = str_repeat('a', 1024 * 1024 - 10).ScanAttachment::TEST_SIGNATURE.str_repeat('b', 100);

        $this->postJson("/api/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('big-notes.txt', $content),
        ])->assertCreated()->assertJsonPath('data.scan_status', 'infected');
    }
}
