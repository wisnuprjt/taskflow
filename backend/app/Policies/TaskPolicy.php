<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Creator, assignee, or admin. Also governs attachment upload/delete. */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->created_by === $user->id
            || $task->assigned_user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->created_by === $user->id;
    }
}
