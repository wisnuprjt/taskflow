<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests run on the "null" broadcaster, which authorises nothing. Switch to Reverb's signer
        // (no server needed to sign) and register the channel rules on it.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }

    public function test_user_can_join_own_private_channel_with_jwt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$user->id}"])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_user_cannot_join_someone_elses_private_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$other->id}"])
            ->assertForbidden();
    }

    public function test_guest_cannot_authorize_channels(): void
    {
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-App.Models.User.1'])
            ->assertUnauthorized();
    }

    public function test_user_can_join_task_channel(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-task.{$task->id}"])
            ->assertOk();
    }

    public function test_presence_channels_share_basic_user_info(): void
    {
        $user = User::factory()->create(['name' => 'Wisnu', 'role' => 'admin']);
        $task = Task::factory()->create();
        $this->actingAs($user, 'api');

        foreach (['presence-online', "presence-task-viewers.{$task->id}"] as $channel) {
            $data = $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $channel])
                ->assertOk()
                ->json('channel_data');

            // Pusher protocol sends user_id as a string; user_info is what the frontend displays.
            $this->assertSame((string) $user->id, json_decode($data, true)['user_id']);
            $this->assertSame(['id' => $user->id, 'name' => 'Wisnu', 'role' => 'admin'], json_decode($data, true)['user_info']);
        }
    }
}
