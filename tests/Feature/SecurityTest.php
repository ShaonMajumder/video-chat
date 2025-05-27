<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use App\Events\NewMessage;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    /** @test */
    public function xss_protection_in_message_sending()
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
                        ])
                         ->postJson('/send-message', [
                             'message' => '<script>alert("XSS")</script>',
                             'receiver_id' => $receiver->id,
                         ]);

        $response->assertStatus(200);

        Event::assertDispatched(NewMessage::class, function ($event) use ($user, $receiver) {
            return $event->sender_id === $user->id &&
                $event->receiver_id === $receiver->id &&
                $event->message === 'alert("XSS")'; // tag removed
        });
        // Note: XSS sanitization is handled by DOMPurify in frontend (chat.blade.php)
    }

    
    /** @test */
    public function unauthorized_user_cannot_access_private_channel()
    {
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'message-box.1',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function login_fails_with_invalid_origin()
    {
        $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:3000');

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ], [
            'Origin' => 'http://malicious.com',
            'Referer' => $frontendUrl . '/login',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Blocked: Invalid origin']);
    }

    /** @test */
    public function login_checks_success()
    {
        
        $user = \App\Models\User::where('email', 'admin@admin.com')->first();
        if(!$user){

        }
        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Login successful']);
    }
}
