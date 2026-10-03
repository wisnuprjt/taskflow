<?php

namespace App\Jobs;

use App\Models\TaskAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnail implements ShouldQueue
{
    use Queueable;

    /** Max thumbnail width in px; smaller images are not upscaled. */
    public const WIDTH = 320;

    /**
     * GD decodes every pixel into memory (~5 bytes each), so the limit is on pixels, not file size:
     * a 7 MB JPEG at 6000x4000 already needs ~120 MB. 50 MP covers modern phone/camera photos.
     */
    public const MAX_PIXELS = 50_000_000;

    /** Headroom for MAX_PIXELS; only raised inside this job, the web requests keep the default. */
    private const MEMORY_LIMIT = '512M';

    /** The attachment may be deleted before the worker picks the job up. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public TaskAttachment $attachment) {}

    public function handle(): void
    {
        $disk = Storage::disk(TaskAttachment::DISK);

        // getimagesize() only reads the header, so oversized images are skipped before decoding.
        $size = @getimagesize($disk->path($this->attachment->file_path));
        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            return;
        }

        ini_set('memory_limit', self::MEMORY_LIMIT);

        // @: undecodable content (corrupt or spoofed image) just means no thumbnail.
        $source = @imagecreatefromstring($disk->get($this->attachment->file_path));
        if ($source === false) {
            return;
        }

        $thumb = imagescale($source, min(self::WIDTH, imagesx($source)));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true); // keep PNG/WebP transparency

        ob_start();
        imagewebp($thumb, null, 80);
        $data = ob_get_clean();

        $path = "thumbnails/{$this->attachment->task_id}/".pathinfo($this->attachment->file_path, PATHINFO_FILENAME).'.webp';
        $disk->put($path, $data);

        $this->attachment->update(['thumbnail_path' => $path]);
    }
}
