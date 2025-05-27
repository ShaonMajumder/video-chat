<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserModelTest extends TestCase
{
    /** @test */
    public function user_implements_jwt_subject()
    {
        $user = new User();
        $this->assertInstanceOf(JWTSubject::class, $user);
    }

    /** @test */
    public function user_can_generate_jwt_identifier()
    {
        $user = new User();
        $user->id = 1; // Directly set id property
        $this->assertEquals(1, $user->getJWTIdentifier());
    }

    /** @test */
    public function user_returns_empty_custom_jwt_claims()
    {
        $user = new User(); // Instantiate, no database
        $this->assertEmpty($user->getJWTCustomClaims());
    }

    /** @test */
    public function user_password_is_hashed()
    {
        $user = new User();
        $password = 'password123';
        $user->password = $password; // Use mutator to hash password

        $this->assertTrue(Hash::check($password, $user->password));
    }
}