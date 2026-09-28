<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['task_id', 'file_name', 'file_path', 'file_size', 'mime_type'])]
class TaskAttachment extends Model
{
    /** Private disk (storage/app/private), never publicly served. */
    public const DISK = 'local';

    public const CREATED_AT = 'uploaded_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (TaskAttachment $attachment) {
            Storage::disk(self::DISK)->delete($attachment->file_path);
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
