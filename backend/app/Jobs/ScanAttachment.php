<?php

namespace App\Jobs;

use App\Events\AttachmentChanged;
use App\Models\TaskAttachment;
use App\Support\Realtime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Simulated antivirus: a real deployment would hand the file to ClamAV or a cloud scanner.
 * Here we flag the EICAR test signature, executable binaries and disguised double extensions.
 */
class ScanAttachment implements ShouldQueue
{
    use Queueable;

    /** Standard antivirus test string, harmless but detected by every real scanner. */
    public const EICAR = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    /**
     * Demo/test marker. Real antivirus (e.g. Windows Defender) deletes EICAR files on sight,
     * so this lets the quarantine flow be exercised without tripping the OS scanner.
     */
    public const TEST_SIGNATURE = 'SIMULATED-VIRUS-SIGNATURE';

    /** Magic bytes of Windows (MZ) and Linux (ELF) executables. */
    private const EXECUTABLE_HEADERS = ["MZ", "\x7FELF"];

    private const DANGEROUS_EXTENSIONS = ['exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'dll', 'js', 'vbs', 'ps1', 'sh', 'php'];

    private const BLOCK_BYTES = 1024 * 1024;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public TaskAttachment $attachment) {}

    public function handle(): void
    {
        $disk = Storage::disk(TaskAttachment::DISK);
        $threat = $this->detectThreat($disk->readStream($this->attachment->file_path));

        if ($threat !== null) {
            // Quarantine: drop the file but keep the row so the user sees why it is gone.
            $disk->delete($this->attachment->file_path);
            $this->attachment->update(['scan_status' => TaskAttachment::SCAN_INFECTED]);
            Realtime::broadcast(new AttachmentChanged($this->attachment, AttachmentChanged::SCANNED));
            Log::warning('Attachment quarantined', ['attachment_id' => $this->attachment->id, 'threat' => $threat]);

            return;
        }

        $this->attachment->update(['scan_status' => TaskAttachment::SCAN_CLEAN]);
        Realtime::broadcast(new AttachmentChanged($this->attachment, AttachmentChanged::SCANNED));

        if (str_starts_with($this->attachment->mime_type, 'image/')) {
            GenerateThumbnail::dispatch($this->attachment);
        }
    }

    /** @param resource $stream */
    private function detectThreat($stream): ?string
    {
        // e.g. "invoice.exe.pdf": every extension except the last one is checked.
        $inner = array_slice(explode('.', strtolower($this->attachment->file_name)), 1, -1);
        if (array_intersect($inner, self::DANGEROUS_EXTENSIONS)) {
            return 'Disguised double extension';
        }

        try {
            // Read in blocks so a 500 MB upload never has to fit in PHP's memory limit.
            // The tail of each block is carried over, so a signature split across two blocks is still found.
            $overlap = max(strlen(self::EICAR), strlen(self::TEST_SIGNATURE)) - 1;
            $carry = '';
            $first = true;

            while (! feof($stream)) {
                $block = $carry.fread($stream, self::BLOCK_BYTES);

                if ($first) {
                    foreach (self::EXECUTABLE_HEADERS as $header) {
                        if (str_starts_with($block, $header)) {
                            return 'Executable binary';
                        }
                    }
                    $first = false;
                }

                if (str_contains($block, self::EICAR) || str_contains($block, self::TEST_SIGNATURE)) {
                    return 'Known virus signature';
                }

                $carry = substr($block, -$overlap);
            }

            return null;
        } finally {
            fclose($stream);
        }
    }
}
