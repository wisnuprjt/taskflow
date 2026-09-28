<?php

namespace App\Http\Requests;

/**
 * Same rules as create, but every field is optional (partial updates).
 */
class UpdateTaskRequest extends StoreTaskRequest
{
    public function rules(): array
    {
        return array_map(
            fn (array $rules) => ['sometimes', ...array_filter($rules, fn ($r) => ! in_array($r, ['required', 'sometimes'], true))],
            parent::rules(),
        );
    }
}
