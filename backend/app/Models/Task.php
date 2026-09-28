<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'description', 'status', 'priority', 'assigned_user_id', 'created_by', 'due_date'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const SORTABLE = ['created_at', 'due_date', 'priority', 'title'];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // DB rows cascade via FK, but the physical files must be removed explicitly.
        static::deleting(function (Task $task) {
            Storage::disk(TaskAttachment::DISK)->delete($task->attachments()->pluck('file_path')->all());
        });
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Apply list filters and sorting from validated query parameters.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['assigned_user_id'] ?? null, fn ($q, $v) => $q->where('assigned_user_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', '%'.$v.'%'));

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if ($column === 'priority') {
            // Enum strings sort alphabetically; map them to their real order (portable across MySQL/SQLite).
            $query->orderByRaw("CASE priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 END {$direction}");
        } else {
            $query->orderBy($column, $direction);
        }

        return $query->orderBy('id', $direction);
    }
}
