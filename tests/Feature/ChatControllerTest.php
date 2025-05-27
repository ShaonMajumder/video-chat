<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Events\NewMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Event;

class ChatControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    /** @test */
    public function it_displays_the_chat_view_with_receiver_id()
    {
        Event::fake();

        $receiverId = 5;

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

        // doesnt work with ->withCookie('token', $tokenValue)
        $response = $this->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                        ])->get('/?receiver=' . $receiverId);

        $response->assertStatus(200);
        $response->assertViewIs('chat');
        $response->assertViewHas('receiverId', $receiverId);
    }

    /** @test */
    public function authenticated_user_can_send_message()
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

        // doesnt work with ->withCookie('token', $tokenValue)
        $response = $this->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                        ])->postJson('/send-message', [
                            'message' => 'Hello World',
                            'receiver_id' => $receiver->id,
                        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'Message Sent!']);
    }

    /** @test */
    public function unauthenticated_user_cannot_send_message()
    {
        $response = $this->postJson('/send-message', [
            'message' => 'Hello World',
            'receiver_id' => 2,
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthorized: No token found']);
    }

    /** @test */
    public function message_broadcast_is_triggered()
    {
        Event::fake();
        $user = User::find(1);
        $receiver = User::find(2);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ]);

        $cookie = $response->getCookie('token');
        $token = $cookie->getValue();

        $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])
            ->postJson('/send-message', [
                'message' => 'Hello World',
                'receiver_id' => $receiver->id,
            ]);

        Event::assertDispatched(NewMessage::class, function ($event) use ($user, $receiver) {
            return $event->sender_id == $user->id &&
                $event->receiver_id == $receiver->id &&
                $event->message == 'Hello World';
        });


    }
}
