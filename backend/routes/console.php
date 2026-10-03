<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Broadcast;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:role {email} {role}', function (string $email, string $role) {
    $newRole = UserRole::tryFrom($role);
    if (! $newRole) {
        $this->error('Role must be one of: '.implode(', ', UserRole::values()));

        return 1;
    }

    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("No user with email {$email}.");

        return 1;
    }

    // Eloquent update: fires the model event that pushes RoleChanged to the user's open tabs.
    $user->update(['role' => $newRole]);
    $this->info("{$user->name} is now {$newRole->value}.");

    return 0;
})->purpose('Change a user role (admin/member); open sessions update in realtime');

Artisan::command('realtime:ping {email}', function (string $email) {
    $user = User::where('email', $email)->firstOrFail();

    Broadcast::private("App.Models.User.{$user->id}")
        ->as('ping')
        ->with(['message' => 'Realtime connection works!'])
        ->sendNow();

    $this->info("Ping sent to {$user->name}.");
})->purpose('Send a test realtime message to a user (requires reverb:start)');
