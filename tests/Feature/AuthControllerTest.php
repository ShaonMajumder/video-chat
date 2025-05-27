<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    /** @test */
    public function it_displays_the_login_form()
    {
        $response = $this->get('/login'); // Adjust this route if different

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456',
        ]);

        // dd($response->getContent());

        $response->assertStatus(200)
                 ->assertJsonStructure(['message'])
                 ->assertJson(['message' => 'Login successful'])
                 ->assertCookie('token');
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function authenticated_user_can_access_me_endpoint()
    {
        // Event::fake();

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
                        ->getJson('/api/me?receiver=2');

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'receiver']);
    }

    /** @test */
    public function logout_removes_token_cookie()
    {
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
                        ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Successfully logged out'])
                 ->assertCookie('jwt', null);
    }
}
