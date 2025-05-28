<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;

trait AuthHelper
{
    protected function getCredentials()
    {
        return config('test.credentials');
    }

    protected function loginUserAndReturnCookie()
    {
        $credentials = $this->getCredentials();
        $response = $this->postJson('/api/login', $credentials);

        $response->assertStatus(200);

        $cookie = $response->getCookie('token');
        $token = $cookie->getValue();
        return $token;
    }

    protected function generateInvalidToken(){
        $invalidToken = 'invalid.' . base64_encode(json_encode(['sub' => 1])) . '.invalid';
        $encryptedToken = Crypt::encrypt($invalidToken);
        return $encryptedToken;
    }
}