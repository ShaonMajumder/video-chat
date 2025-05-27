<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;

class RateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    /** @test */
    public function login_is_rate_limited()
    {
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'admin@admin.com',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
                 ->assertJson(['error' => 'Too many login attempts. Please wait a minute.']);
    }

    /** @test */
    public function chat_is_rate_limited()
    {
        Event::fake();

        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ]);

        $response->assertStatus(200);

        $cookie = $response->getCookie('token');
        $token = $cookie->getValue();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                            ])->postJson('/send-message', [
                                'message' => 'Hello World',
                                'receiver_id' => 2,
                            ]);
            $response->assertStatus(200);
        }

        $response = $this->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                        ])->postJson('/send-message', [
                            'message' => 'Hello World',
                            'receiver_id' => 2,
                        ]);

        $response->assertStatus(429);
    }
}
