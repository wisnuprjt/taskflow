<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
{
    public function rules(): array
    {
        $sortable = collect(Task::SORTABLE)->flatMap(fn ($c) => [$c, "-{$c}"])->all();

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', 'nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'assigned_user_id' => ['sometimes', 'nullable', 'integer'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', Rule::in($sortable)],
        ];
    }
}
