```bash
docker exec -it laravel-app bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

config/auth.php :
```php
'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],


    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],
```

```bash
php artisan make:controller AuthController
```

AuthController:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            $encryptedToken = Crypt::encrypt($token);

            $cookie = cookie(
                'token',
                $encryptedToken,
                60,             // 60 minutes
                '/',            // path
                null,           // domain
                false,          // secure: false for HTTP on localhost
                true,           // HttpOnly
                false,          // raw
                'Lax'           // SameSite policy (or 'Strict', 'None')
            );

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'token' => $encryptedToken
                ]
            ])->cookie($cookie);
        } catch (JWTException $e) {
             return response()->json(['message' => 'Could not create token'], 500);
        }
    }

    public function me(Request $request)
    {
        $receiver = $request->receiver;
        $userCacheKey = "user:{$receiver}";
        $receiverUserData = Cache::store('redis')->get($userCacheKey);
        if (!$receiverUserData) {
            $receiverUserData = User::find($receiver);
            Cache::store('redis')->put($userCacheKey, $receiverUserData, now()->addHours(24));
        }
            
        if (!$receiver) {
            return response()->json(['error' => 'Receiver not found'], 404);
        }
        
        return response()->json([
            'user' => JWTAuth::user(),
            'receiver' => $receiverUserData,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out'])
                            ->cookie('jwt', null, -1);
            // return response()->json(['message' => 'Logged out'])->withCookie(cookie()->forget('token'));
        } catch (JWTException $e) {
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }
}
```

config/broadcasting.php :
```php
'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https', //true,                
                'host' => env('PUSHER_HOST','127.0.0.1'),
                'port' => env('PUSHER_PORT','6001'),
                'scheme' => env('PUSHER_SCHEME', 'http'),
                'verify_signature' => true, // Enable signature verification for security
            ],
        ],
```

routes/web.php :
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');

Route::middleware(['auth.cookie'])->group(function () {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:chat');
});
```
routes/api.php :
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::middleware(['auth.cookie','throttle:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
```

routes/channels.php :
```php
<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


// Single Channel per User:
// The current setup assumes each user subscribes to their own message-box.{userId} channel to receive messages. This is fine for one-on-one chats but may need adjustment for group chats or multiple concurrent conversations.
// If users need to receive messages from multiple senders, you might need additional channels or a different naming convention (e.g., conversation.{conversationId}).

Broadcast::channel('message-box.{userId}', function ($user, $userId) {
    if (!$user) {
        Log::warning('Channel authorization failed: No authenticated user', [
            'channel_user_id' => $userId,
        ]);
        return false;
    }

    $authorized = (int) $user->id === (int) $userId;

    $logContext = [
        'user_id' => $user->id,
        'channel_user_id' => $userId,
        'authorized' => $authorized,
    ];

    if (app()->environment('local')) {
        Log::debug('Channel authorization', $logContext);
    } elseif (!$authorized) {
        Log::warning('Channel authorization failed', $logContext);
    }

    return $authorized;
});

Broadcast::channel('presence.online', function ($user) {
    Log::info('Online presence', ['id' => $user->id, 'name' => $user->name]);
    return ['id' => $user->id, 'name' => $user->name];
});
```

config : config/websockets.php
```php
    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'host' => env('PUSHER_APP_HOST'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false, // false ??? true - is it secure
            'enable_statistics' => true,
            // 'allowed_origins' => explode(',', env('WEBSOCKETS_ALLOWED_ORIGINS', [])),

            'verify_peer' => false, // setting disables SSL peer verification, which is insecure for production.
            'auth' => [
                'enabled' => true,
                'driver' => 'jwt', // Enable JWT auth
                'jwt' => [
                    'secret' => env('JWT_SECRET'),
                    'algo' => 'HS256',
                ],
            ],
        ],
    ],
```

exception App\Http\Middleware\EncryptCookies:
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'token'
    ];
}
```

config/app.php :
```php
'providers' => [
    ....
    App\Providers\BroadcastServiceProvider::class,
]
```
bootstrap.js
```js
window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: false,
    wsHost: window.location.hostname,
    wsPort: process.env.PUSHER_PORT | '6001',
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    withCredentials: true,
    // jwt cookie is sent with credentials: 'include'
});
```


```bash
php artisan make:seed UserSeeder
```

UserSeeder :
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!App::environment('local')) {
            $this->command->info('UserSeeder skipped: not in local environment.');
            return;
        }

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => '123456',
            ],
            [
                'name' => 'Admin 2',
                'email' => 'admin2@admin.com',
                'password' => '123456',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );
        }

        $this->command->info('UserSeeder ran successfully.');
    }
}
```

```bash
php artisan queue:table
php aritsan migrate
php artisan db:seed --class=UserSeeder
```

User:
```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```


php artisan make:middleware AuthenticateWithCookie
Register the middleware in app/Http/Kernel.php:

```php
protected $routeMiddleware = [
    // ...
    'auth.cookie' => \App\Http\Middleware\AuthenticateWithCookie::class,
];
```

AuthenticateWithCookie:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->cookie('token') ?? $request->bearerToken();
            $decryptedToken = Crypt::decrypt($token);
            $user = JWTAuth::setToken($decryptedToken)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized: Invalid token'], 401);
            }

            $request->merge(['user' => $user]);
            
        } catch (JWTException $e) {
            Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['message' => 'Unauthorized: Token error'], Response::HTTP_UNAUTHORIZED);

        } catch (\Exception $e) {
            Log::error('Authentication middleware exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['message' => 'Unauthorized: Unexpected error'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $next($request);
    }
}
```

BroadcastServiceProvider:
```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes(['middleware' => ['auth.cookie']]);
        require base_path('routes/channels.php');
    }
}
```

login.blade.php:
```php
@extends('layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    .login-container {
        background: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-width: 400px;
        margin: 3rem auto;
    }

    h1 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #111827;
        text-align: center;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    input[type="email"],
    input[type="password"] {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 1rem;
        transition: border-color 0.2s ease;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    button[type="submit"] {
        background-color: #2563eb;
        color: white;
        font-weight: 700;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background-color 0.3s ease;
    }

    button[type="submit"]:hover {
        background-color: #1e40af;
    }

    .error-messages {
        background: #fee2e2;
        color: #b91c1c;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="login-container" role="main" aria-labelledby="login-title">
    <h1 id="login-title">Login to Your Account</h1>

    <form id="loginForm" novalidate>
        <input type="email" name="email" placeholder="Email address" required autocomplete="email" />
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <button type="submit">Login</button>
    </form>

    <div id="error-container" class="error-messages" style="display: none;" role="alert" aria-live="assertive"></div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = e.target;
    const email = form.email.value;
    const password = form.password.value;
    const errorContainer = document.getElementById('error-container');

    errorContainer.style.display = 'none';
    errorContainer.innerHTML = '';

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ email, password }),
            credentials: 'same-origin' // allow Laravel to set the HttpOnly cookie
        });

        const data = await response.json();
        if (response.ok) {
            window.location.href = '/';
        } else {
            const message = data.message || 'Login failed';
            errorContainer.innerHTML = `<p>${message}</p>`;
            errorContainer.style.display = 'block';
        }
    } catch (error) {
        errorContainer.innerHTML = `<p>Something went wrong. Please try again.</p>`;
        errorContainer.style.display = 'block';
    }
});

</script>
@endsection
```

chat.blade.php :
```blade
@extends('layouts.app')

@section('title', 'Live Chat')

@section('styles')
<style>
    .chat-wrapper {
        flex: 3; /* takes 3 parts of available space */
        display: flex;

        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        height: 75vh;
        max-width: 700px;
        margin: 2rem auto 0;
        overflow: hidden;
    }

    header.chat-header {
        background: #2563eb;
        color: white;
        font-weight: 700;
        font-size: 1.5rem;
        padding: 1rem 1.5rem;
        user-select: none;
    }

    ul#messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem 1.5rem;
        margin: 0;
        list-style: none;
        background: #f3f4f6;
        scroll-behavior: smooth;
    }

    ul#messages li.message {
        max-width: 70%;
        margin-bottom: 1rem;
        display: flex;
        word-break: break-word;
    }

    .bubble {
        padding: 12px 18px;
        border-radius: 20px;
        font-size: 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        line-height: 1.3;
    }

    .bubble-left {
        background: #e0e7ff;
        color: #1e40af;
        margin-right: auto;
        border-bottom-left-radius: 0;
    }

    .bubble-right {
        background: #2563eb;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 0;
    }

    .input-area {
        display: flex;
        padding: 1rem 1.5rem;
        border-top: 1px solid #ddd;
        background: white;
    }

    .input-area input[type="text"] {
        flex-grow: 1;
        padding: 12px 16px;
        font-size: 1rem;
        border-radius: 9999px;
        border: 1px solid #cbd5e1;
        outline-offset: 2px;
        transition: border-color 0.2s ease;
    }

    .input-area input[type="text"]:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    .input-area button {
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 9999px;
        padding: 0 24px;
        margin-left: 1rem;
        font-weight: 700;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }

    .input-area button:hover {
        background: #1e40af;
    }

    @media (max-width: 640px) {
        .chat-wrapper {
            height: 100vh;
            border-radius: 0;
            margin: 0;
        }
    }


    .chat-container {
        display: flex;
        gap: 1rem; /* spacing between left and right */
        height: 80vh; /* or any height you want */
    }


    /* Online users panel on the right */
    .online-users {
        flex: 1; /* takes 1 part of available space */
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 1rem;
        background: #f9f9f9;
        overflow-y: auto; /* scroll if too many users */
    }



    /* Online user list style */
    #user-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

</style>
@endsection

@section('content')
<div class="chat-container" role="main" aria-label="Live chat interface">
    @if(!empty($receiverId))
    <div class="chat-wrapper" aria-label="Chat messages area">
        <header class="chat-header" id="chat-header">🗨️ Live Video Chat</header>

        <ul id="messages" aria-live="polite" aria-relevant="additions"></ul>

        <div class="input-area">
            <input type="text" id="message" placeholder="Type your message..." autocomplete="off" aria-label="Message input" />
            <button id="sendBtn" aria-label="Send message">Send</button>
        </div>
    </div>
    @endif

    <div class="online-users" aria-label="Online users list">
        <header class="chat-header">🟢 Online Users</header>
        <ul id="user-list" aria-label="Online users list">
            <!-- Populated by JS -->
        </ul>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
        // axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
    //     cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    //     wsHost: window.location.hostname,
    //     wsPort: 6001,
    //     forceTLS: false,
    //     disableStats: true,
    //     enabledTransports: ['ws', 'wss'],
    // });

    // const channel = pusher.subscribe('chat');
    // const currentId = Math.random().toString(36).substr(2, 9);
    const urlParams = new URLSearchParams(window.location.search);
    const receiverId = urlParams.get('receiver');

    // channel.bind('App\\Events\\MessageSent', function(data) {
    //     log(data)
    //     const isSelf = data.sender_id === currentId;

    //     const li = document.createElement("li");
    //     li.classList.add('message');

    //     const bubble = document.createElement("div");
    //     bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //     bubble.textContent = data.message;

    //     li.appendChild(bubble);
    //     document.getElementById("messages").appendChild(li);

    //     // Scroll down
    //     const messages = document.getElementById('messages');
    //     messages.scrollTop = messages.scrollHeight;
    // });

    // document.getElementById('sendBtn').addEventListener('click', sendMessage);
    // document.getElementById('message').addEventListener('keyup', function(e) {
    //     if (e.key === 'Enter') sendMessage();
    // });

    // function sendMessage() {
    //     const input = document.getElementById('message');
    //     const message = input.value.trim();
    //     alert(message)
    //     if (!message) return;
    //     axios.post('{{ route('chat.send') }}', { message, receiver_id: receiverId })
    //         .then(() => {
    //             input.value = '';
    //         })
    //         .catch(err => {
    //             alert('Message failed to send. Please try again.');
    //             console.error(err);
    //         });
            
    // }

    async function subscribeToChannel(receiverId) {
        try {
            const response = await fetch(`/api/me?receiver=${receiverId}`, {
                credentials: 'include'
            });
            const user = await response.json();

            const currentUserId = user.user.id;
            if (user.receiver) {
                document.getElementById("chat-header").textContent = `🗨️ ${user.receiver.name}`;
            }

            log('user', user);
            log('channel', `message-box.${currentUserId}`);

            window.Echo.private(`message-box.${currentUserId}`)
                .listen('NewMessage', (data) => {
                    const isSelf = data.sender_id === currentUserId;
                    const li = document.createElement("li");
                    li.classList.add('message');

                    const bubble = document.createElement("div");
                    bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
                    bubble.textContent = DOMPurify.sanitize(data.message);

                    li.appendChild(bubble);
                    document.getElementById("messages").appendChild(li);

                    const messages = document.getElementById('messages');
                    messages.scrollTop = messages.scrollHeight;

                    log('data', data, 'isSelf', isSelf);
                });

            // Attach send button events
            document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
            document.getElementById('message')?.addEventListener('keyup', function (e) {
                if (e.key === 'Enter') sendMessage();
            });

            function sendMessage() {
                const input = document.getElementById('message');
                const message = input.value.trim();
                if (!message) return;

                axios.post('{{ route('chat.send') }}', {
                    message,
                    receiver_id: receiverId
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).then(() => {
                    input.value = '';
                }).catch(err => {
                    alert('Message failed to send. Please try again.');
                    console.error(err);
                });

                const li = document.createElement("li");
                li.classList.add('message');

                const bubble = document.createElement("div");
                bubble.classList.add('bubble', 'bubble-right');
                bubble.textContent = DOMPurify.sanitize(message);

                li.appendChild(bubble);
                document.getElementById("messages").appendChild(li);

                const messages = document.getElementById('messages');
                messages.scrollTop = messages.scrollHeight;
            }

            return user;
        } catch (error) {
            console.error("Error subscribing to channel:", error);
            return null;
        }
    }

    // async function subscribeToChannel(receiverId) {
    //     fetch(`/api/me?receiver=${receiverId}`, {
    //         credentials: 'include' // Send jwt cookie
    //     })
    //     .then(response => response.json())
    //     .then(user => {
    //         const currentUserId = user.user.id;
    //         if(user.receiver){
    //             document.getElementById("chat-header").textContent = `🗨️ ${user.receiver.name}`;
    //         }
            
    //         log('user',user)
    //         log('channel', `message-box.${currentUserId}`)
    //         window.Echo.private(`message-box.${currentUserId}`)
    //             .listen('NewMessage', (data) => {
    //                 const isSelf = data.sender_id === currentUserId;
    //                 const li = document.createElement("li");
    //                 li.classList.add('message');

    //                 const bubble = document.createElement("div");
    //                 bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //                 bubble.textContent = DOMPurify.sanitize(data.message);

    //                 li.appendChild(bubble);
    //                 document.getElementById("messages").appendChild(li);

    //                 // Auto scroll to latest
    //                 const messages = document.getElementById('messages');
    //                 messages.scrollTop = messages.scrollHeight;

    //                 log('data',data,'iseelf', isSelf)
    //             });
    //     });


    //      // Send message handler
    //     document.getElementById('sendBtn')>.addEventListener('click', sendMessage);
    //     document.getElementById('message')?.addEventListener('keyup', function(e) {
    //         if (e.key === 'Enter') sendMessage();
    //     });

    //     function sendMessage() {
    //         const input = document.getElementById('message');
    //         const message = input.value.trim();
    //         if (!message) return;

    //         axios.post('{{ route('chat.send') }}', {
    //             message,
    //             receiver_id: receiverId
    //         }, {
    //             headers: {
    //                 'X-CSRF-TOKEN': csrfToken
    //             }
    //         }).then(() => {
    //             input.value = '';
    //         }).catch(err => {
    //             alert('Message failed to send. Please try again.');
    //             console.error(err);
    //         });

    //         const isSelf = true;// data.sender_id === currentId;

    //         const li = document.createElement("li");
    //         li.classList.add('message');

    //         const bubble = document.createElement("div");
    //         bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //         bubble.textContent = DOMPurify.sanitize(message);

    //         li.appendChild(bubble);
    //         document.getElementById("messages").appendChild(li);

    //         // Scroll down
    //         const messages = document.getElementById('messages');
    //         messages.scrollTop = messages.scrollHeight;
    //     }
    // }

    function showOnlineUsers(currentUserId) {

        window.Echo.join('presence.online')
            .here(users => {
                const filteredUsers = users.filter(user => user.id !== currentUserId.id);
                console.log('users',filteredUsers)
                renderOnlineUsers(filteredUsers);
            })
            .joining(user => {
                addOnlineUser(user);
            })
            .leaving(user => {
                removeOnlineUser(user);
            });
    }

    function renderOnlineUsers(users) {
        const userList = document.getElementById('user-list');
        userList.innerHTML = ''; // Clear the list
        users.forEach(user => addOnlineUser(user));
    }

    function addOnlineUser(user) {
        const userList = document.getElementById('user-list');
        const li = document.createElement('li');
        li.classList.add('message'); // Keep styling consistent if needed
        li.innerHTML = `
            <div class="bubble bubble-left" style="cursor:pointer" onclick="window.location.href='?receiver=${user.id}'" role="button" tabindex="0" aria-label="Chat with ${DOMPurify.sanitize(user.name)}">
                👤 ${DOMPurify.sanitize(user.name)}
            </div>`;
        userList.appendChild(li);
    }

    function removeOnlineUser(user) {
        const userList = document.getElementById('user-list');
        const children = Array.from(userList.children);
        for (const child of children) {
            if (child.innerText.includes(user.name)) {
                child.remove();
            }
        }
    }

    // if (receiverId) {
    //     const currentUserId = await subscribeToChannel(receiverId);
    //     showOnlineUsers(currentUserId);
    // }

    (async () => {
        const responseSubscription = await subscribeToChannel(receiverId);
        const currentUser = responseSubscription.user;
        if (currentUser) {
            showOnlineUsers(currentUser); // Do something with currentUser
        }
    })();
});
</script>

@endsection
```

resources/js/app.js :
```js
require('./bootstrap');
require('./utils');
```

resources/js/utils.js :
```js
// resources/js/utils.js
(function () {
    const isProduction = document.querySelector('meta[name="app-env"]')?.content === 'production';

    window.log = (...args) => {
        if (!isProduction) {
            console.log(...args);
        }
    };
})();
```

app.blade.php :
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') - Video Chat App</title>

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />

    <style>
        /* Reset & base */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        header.site-header {
            background: #2563eb;
            color: white;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.25rem;
            user-select: none;
        }

        main.site-content {
            flex-grow: 1;
            padding: 2rem;
            max-width: 700px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        footer.site-footer {
            text-align: center;
            padding: 1rem 2rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>

    @yield('styles')
</head>
<body>
    <header class="site-header">
        Video Chat App
    </header>

    <main class="site-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        &copy; {{ date('Y') }} Video Chat App. All rights reserved.
    </footer>

    @yield('scripts')
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
```
---------------------

```bash
php artisan make:event NewMessage
```

edit :
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender_id;
    public $receiver_id;
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($sender_id, $receiver_id, $message)
    {
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('message-box.' . $this->receiver_id);
    }

    public function broadcastWith()
    {
        return [
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'message' => $this->message,
        ];
    }
}
```

```bash
php artisan make:controller ChatController
```

edit ChatController :
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NewMessage;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function sendMessage(Request $request)
    {
        broadcast(new NewMessage( $request->user->id, $request->receiver_id, $request->message))->toOthers();
        return response()->json(['status' => 'Message Sent!']);
    }
}
```


```bash
docker exec -it laravel-app bash
php artisan tinker
```

Trigger the event:
```php
event(new \App\Events\NewMessage(1, 2, 'Hello from server!'));
```

rate limiting RouteServiceProvider:
```
protected function configureRateLimiting()
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json(['error' => 'Too many login attempts. Please wait a minute.'], 429);
            });
        });

        RateLimiter::for('chat', function (Request $request) {
            // $userId = $request->user('api')?->id ?? $request->ip();
            $userId = optional($request->user('api'))->id ?? $request->ip();
            return Limit::perMinute(30)->by($userId);
        });
        
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Global rate limit
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(100000)->by('global')->response(function () {
                return response()->json(['error' => 'Server busy. Please try again later.'], 429);
            });
        });
    }
```

## Debugging WebSocket
```bash
docker exec -it laravel-app bash
cat /var/log/websockets.out.log
```

 supervisorctl status
nginx                            RUNNING   pid 10, uptime 0:00:17
php-fpm                          RUNNING   pid 11, uptime 0:00:17
queue                            RUNNING   pid 12, uptime 0:00:17
websockets                       RUNNING   pid 13, uptime 0:00:17
root@c8b7803ecf91:/var/www/html# supervisorctl restart websockets
websockets: stopped
websockets: started
root@c8b7803ecf91:/var/www/html#

Check queries -http://localhost:8000/telescope/requests

---- make it scalable for 200M+ users ----