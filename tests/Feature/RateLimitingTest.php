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
use Tests\Traits\AuthHelper;
// use Illuminate\Support\Facades\Log;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class RateLimitingTest extends TestCase
{
    use AuthHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        // RateLimiter::for('global', function (Request $request) {
        //     return Limit::perMinute(40)->by('global')->response(function () {
        //         return response()->json(['error' => 'Server busy. Please try again later.'], 429);
        //     });
        // });
    }

    protected function tearDown(): void
    {
        // RateLimiter::clear('global');
        // parent::tearDown();
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

    /** @test */
    // public function global_rate_limiter_restricts_requests_with_high_limit()
    // {
    //     $token= $this->loginUserAndReturnCookie();

    //     for ($i = 0; $i < 40; $i++) {
    //         RateLimiter::hit('global', 60);
    //         $response = $this->withHeaders([
    //                         'Authorization' => 'Bearer ' . $token,
    //                     ])
    //                     ->getJson('/login');
    //         $response->assertStatus(200);
    //     }

    //     $response = $this->withHeaders([
    //                         'Authorization' => 'Bearer ' . $token,
    //                     ])
    //                     ->getJson('/login');

    //     $response->assertStatus(429)
    //             ->assertJson(['error' => 'Server busy. Please try again later.']);
    // }
}
