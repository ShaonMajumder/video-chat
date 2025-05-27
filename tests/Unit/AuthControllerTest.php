<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Mockery;
use Carbon\Carbon;

class AuthControllerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function login_returns_success_response_on_valid_credentials()
    {
        $controller = new AuthController();

        $request = Request::create('/api/login', 'POST', [
            'email' => 'admin@admin.com',
            'password' => '123456'
        ]);

        $fakeToken = 'fake.jwt.token';

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'admin@admin.com', 'password' => '123456'])
            ->andReturn($fakeToken);

        Crypt::shouldReceive('encrypt')
            ->once()
            ->with($fakeToken)
            ->andReturn('encrypted-token');

        $response = $controller->login($request);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Login successful', $response->getData()->message);
        $this->assertNotEmpty($response->headers->getCookies());
    }

    /** @test */
    public function login_returns_unauthorized_on_invalid_credentials()
    {
        $controller = new AuthController();

        $request = Request::create('/api/login', 'POST', [
            'email' => 'admin@admin.com',
            'password' => 'wrong'
        ]);

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn(false);

        $response = $controller->login($request);

        $this->assertEquals(401, $response->status());
        $this->assertEquals('Invalid credentials', $response->getData()->message);
    }

    // /** @test */
    // public function me_returns_user_and_cached_receiver()
    // {
    //     // Arrange
    //     $controller = new AuthController();
    //     $request = Request::create('/api/me?receiver=2', 'GET');
    //     $user = new User(['id' => 1, 'name' => 'Test User']);
    //     $receiverUser = new User(['id' => 2, 'name' => 'Receiver']);

    //     // Mock JWTAuth
    //     JWTAuth::shouldReceive('user')
    //            ->once()
    //            ->andReturn($user);

    //     // Mock Cache facade for Redis store
    //     Cache::shouldReceive('store')
    //          ->with('redis')
    //          ->andReturnSelf();
    //     Cache::shouldReceive('get')
    //          ->once()
    //          ->with('user:2')
    //          ->andReturn(null);
    //     Cache::shouldReceive('put')
    //          ->once()
    //          ->with('user:2', Mockery::type(User::class), Mockery::type(Carbon::class));

    //     // Mock User model static methods
    //     $userMock = Mockery::mock('alias:App\Models\User');
    //     $queryMock = Mockery::mock(Builder::class);
    //     $userMock->shouldReceive('query')
    //              ->once()
    //              ->andReturn($queryMock);
    //     $queryMock->shouldReceive('find')
    //               ->once()
    //               ->with(2)
    //               ->andReturn($receiverUser);

    //     // Act
    //     $response = $controller->me($request);

    //     // Assert
    //     $this->assertEquals(200, $response->status());
    //     $this->assertEquals(1, $response->getData()->user->id);
    //     $this->assertEquals(2, $response->getData()->receiver->id);
    // }

    /** @test */
    public function me_returns_error_when_receiver_missing()
    {
        $controller = new AuthController();

        $request = Request::create('/api/me', 'GET');

        $response = $controller->me($request);

        $this->assertEquals(404, $response->status());
        $this->assertEquals('Receiver not found', $response->getData()->error);
    }

    /** @test */
    public function logout_invalidates_token_and_removes_cookie()
    {
        $controller = new AuthController();

        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn('some.token');

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with('some.token');

        $request = Request::create('/api/logout', 'POST');

        $response = $controller->logout($request);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Successfully logged out', $response->getData()->message);
    }

    /** @test */
    public function logout_handles_exception_gracefully()
    {
        $controller = new AuthController();

        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andThrow(new JWTException('Token error'));

        $request = Request::create('/api/logout', 'POST');

        $response = $controller->logout($request);

        $this->assertEquals(500, $response->status());
        $this->assertEquals('Logout failed', $response->getData()->message);
    }
}
