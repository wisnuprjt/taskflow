<?php

namespace App\Events;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Tells a signed-in user, on their own channel only, that their role (and so their permissions) changed. */
class RoleChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->user->id}");
    }

    public function broadcastAs(): string
    {
        return 'role.changed';
    }

    public function broadcastWith(): array
    {
        return ['user' => UserResource::make($this->user)->resolve()];
    }
}
