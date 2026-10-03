<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['task_id', 'file_name', 'version', 'file_path', 'thumbnail_path', 'file_size', 'mime_type', 'scan_status'])]
class TaskAttachment extends Model
{
    /** Private disk (storage/app/private), never publicly served. */
    public const DISK = 'local';

    public const SCAN_PENDING = 'pending';

    public const SCAN_CLEAN = 'clean';

    public const SCAN_INFECTED = 'infected';

    public const CREATED_AT = 'uploaded_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (TaskAttachment $attachment) {
            Storage::disk(self::DISK)->delete(array_filter([$attachment->file_path, $attachment->thumbnail_path]));
        });
    }

    /** Every version of this file within the same task, newest first. */
    public function versions(): Collection
    {
        return static::where('task_id', $this->task_id)
            ->where('file_name', $this->file_name)
            ->orderByDesc('version')
            ->get();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
