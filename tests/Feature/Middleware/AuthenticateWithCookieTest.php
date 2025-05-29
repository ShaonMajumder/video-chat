<?php

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\User;
use Tests\Traits\AuthHelper;

class AuthenticateWithCookieTest extends TestCase
{
    use AuthHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        // Temp test route protected by middleware
        Route::middleware('web', \App\Http\Middleware\AuthenticateWithCookie::class)
            ->get('/middleware-test', fn() => response()->json(['message' => 'Passed']));
    }

    /** @test */
    public function it_returns_unauthorized_if_no_token()
    {
        $response = $this->get('/middleware-test');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthorized: No token found']);
    }

    /** @test */
    public function it_returns_unauthorized_if_token_cannot_be_decrypted()
    {
        $invalidToken = 'invalid-token-value';
        $cookie = cookie('token', $invalidToken);
        $response = $this->withHeaders([
                                'Authorization' => 'Bearer ' . $cookie,
                            ])->get('/middleware-test');

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthorized: Unexpected error']);
    }

    /** @test */
    public function it_returns_unauthorized_if_jwt_token_is_invalid()
    {
        $token = $this->generateInvalidToken();
        $response = $this->withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                            ])->get('/middleware-test');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthorized: Token error']);
    }

    /** @test */
    public function it_allows_access_if_token_is_valid()
    {
        $token = $this->loginUserAndReturnCookie();
        $response = $this->withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                            ])->get('/middleware-test');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Passed']);
    }

}
