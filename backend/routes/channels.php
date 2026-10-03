<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Personal channel: role changes and other events aimed at one user.
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Task list refreshes: any user who may list tasks.
Broadcast::channel('tasks', function (User $user) {
    return $user->can('viewAny', Task::class);
});

// Live updates for one task (comments, attachments, edits): anyone allowed to view the task.
Broadcast::channel('task.{task}', function (User $user, Task $task) {
    return $user->can('view', $task);
});

// Presence: who is online anywhere in the app. Returning an array (not true) makes it a presence
// channel, and that array is what other members see about this user.
Broadcast::channel('online', function (User $user) {
    return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
});

// Presence: who is looking at this task right now; also carries "typing…" whispers between browsers.
Broadcast::channel('task-viewers.{task}', function (User $user, Task $task) {
    return $user->can('view', $task) ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role] : false;
});
