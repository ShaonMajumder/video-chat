<?php

namespace Tests\Feature\Broadcast;

use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Event;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    public function test_broadcast_auth_endpoint()
    {
        Event::fake();

        $user = User::find(1);
        $receiver = User::find(2);
        // $token = JWTAuth::fromUser($user);
        // $encryptedToken = Crypt::encrypt($token);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ]);

        $response->assertStatus(200);

        $cookie = $response->getCookie('token');
        $token = $cookie->getValue();

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-message-box.$user->id",
            ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['auth']);
    }

    public function test_new_message_event_broadcasts()
    {
        Broadcast::shouldReceive('queue')
            ->once()
            ->andReturnUsing(function ($event) {
                $this->assertInstanceOf(\App\Events\NewMessage::class, $event);
                $this->assertEquals('private-message-box.2', $event->broadcastOn()->name);
                $this->assertEquals([
                    'sender_id' => 1,
                    'receiver_id' => 2,
                    'message' => 'Hello from server!',
                ], $event->broadcastWith());
            });

        event(new \App\Events\NewMessage(1, 2, 'Hello from server!'));
    }
}