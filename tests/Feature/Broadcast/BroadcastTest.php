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

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_broadcast_auth_endpoint()
    {
        // Use seeded user
        $user = User::find(2);
        $this->assertNotNull($user, 'User with id = 2 not found');

        // Generate and encrypt JWT token
        try {
            $token = JWTAuth::fromUser($user);
            Log::debug('Generated JWT token', ['token' => $token]);
            $encryptedToken = Crypt::encryptString($token); // Use encryptString
            Log::debug('Encrypted token', ['encrypted_token' => $encryptedToken]);
        } catch (\Exception $e) {
            Log::error('Token generation error', ['error' => $e->getMessage()]);
            $this->fail('Failed to generate token: ' . $e->getMessage());
        }

        // Test authorized channel
        $response = $this->withCookies(['token' => $encryptedToken])
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-message-box.2',
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