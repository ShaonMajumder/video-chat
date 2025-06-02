## 📦 2. Clone Laravel Soketi Docker Template

```bash
git clone https://github.com/ShaonMajumder/docker-template-laravel-12-php-8.3-npm-mysql-redis-nginx-queue-soketi.git tutorial_soketi-laravel-12
cd tutorial_soketi-laravel-12
```

---

## 🛠️ 3. Build and Run Docker Containers

```bash
docker-compose --env-file environment/.env up --build
```

---

## 🔍 4. Verify Soketi Server is Running

### In Browser

Visit: [http://localhost:6001](http://localhost:6001)

Expected Output:
```text
ok
```

### Inside Container

```bash
docker exec -it laravel-app bash
curl http://soketi-server:6001
```

Expected Output:
```text
ok
```

---

## 📦 5. Install NodeJS Dependencies (Vite)

```bash
npm install
npm run build
```

---

## 📡 6. Install Broadcasting in Laravel

```bash
php artisan install:broadcasting
```

### Select:
```bash
 Which broadcasting driver would you like to use? ────────────┐
 │   ○ Laravel Reverb                                           │
 │ › ● Pusher                                                   │
 │   ○ Ably  
```
- Driver: `Pusher`

```bash
 Which broadcasting driver would you like to use? ────────────┐
 │ Pusher                                                       │
 └──────────────────────────────────────────────────────────────┘

 ┌ Pusher App ID ───────────────────────────────────────────────┐
 │ 1234                                                         │
 └──────────────────────────────────────────────────────────────┘

 ┌ Pusher App Key ──────────────────────────────────────────────┐
 │ •••••••••                                                    │
 └──────────────────────────────────────────────────────────────┘

 ┌ Pusher App Secret ───────────────────────────────────────────┐
 │ ••••••••••                                                   │
 └──────────────────────────────────────────────────────────────┘

 ┌ Pusher App Cluster ──────────────────────────────────────────┐
 │ › ● mt1                                                    ┃ │
 │   ○ us2                                                    │ │
 │   ○ us3                                                    │ │
 │   ○ eu                                                     │ │
 │   ○ ap1    
```

- Select and enter these Values:
- App ID: `1234`
- Key/Secret: dummy/test values
- Cluster: Select `mt1`and press Enter
- `pusher-php-server` will be installed by default

```bash
 Would you like to install and build the Node dependencies required for br… ┐
 │ ● Yes / ○ No   

    
   INFO  Installing and building Node dependencies.


up to date, audited 98 packages in 1s

22 packages are looking for funding
  run `npm fund` for details

found 0 vulnerabilities

> build
> vite build

vite v6.3.5 building for production...
✓ 59 modules transformed.
public/build/manifest.json              0.27 kB │ gzip:  0.15 kB
public/build/assets/app-BLzl-bg6.css   33.54 kB │ gzip:  8.51 kB
public/build/assets/app-CqvyoFfN.js   114.41 kB │ gzip: 36.11 kB
✓ built in 2.09s
```

INFO : laravel-echo and pusher-js npm library will be installed by broadcasting installer.
If install fails, manually run:

```bash
npm install laravel-echo pusher-js
npm run build
```

---

## ⚙️ 7. Verify `config/broadcasting.php`

```php
'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => false, // true, revert and check
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],
```

---

## ✉️ 8. Create and Dispatch an Event

```bash
php artisan make:event NewMessage
```

### Edit `NewMessage.php`:
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
     * @return array<int, \Illuminate\Broadcasting\Channel>
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

### Test in Tinker:

```bash
php artisan tinker
```
shell will appear like this:
```bash
Psy Shell v0.12.8 (PHP 8.3.21 — cli) by Justin Hileman
>
```
Now trigger the event:
```bash
event(new \App\Events\NewMessage(1, 'Hello from server!'))
```
- output: [] - if '[]' , then it is ok.

### Check Soketi Logs:
In another terminal, immediately check log, after previously triggering the event:
```bash
docker-compose logs soketi
```

Expected Payload:
```bash
.....
 [Sun May 25 2025 19:13:33 GMT+0000 (Coordinated Universal Time)] ⚡ HTTP Payload received   

{
  name: 'App\\Events\\NewMessage',
  data: '{"sender_id":1,"message":"Hello from server!"}',
  channel: 'message-box'
}
.....
```

---

## 💡 9. Configure Frontend Listener

Edit `resources/js/echo.js`:
```js
import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: false, //true,
    wsHost: 'localhost', //import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
    enabledTransports: ["ws", "wss"],
});
```

### Modify `welcome.blade.php`, at end of before </body>:
```blade
....
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.Echo) {
                    window.Echo.channel('message-box').listen('NewMessage', (e) => {
                        console.log('Message received', e);
                    });
                } else {
                    console.error('Echo is not defined');
                }
            });
        </script>
    </body>
```

---

```bash
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

app\Http\Controllers\AuthController.php :
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
            ])->cookie($cookie);
        } catch (JWTException $e) {
             return response()->json(['message' => 'Could not create token'], 500);
        }
    }

    public function me(Request $request)
    {
        $receiver = $request->receiver;
        if($receiver){
            $userCacheKey = "user:{$receiver}";
            $receiverUserData = Cache::store('redis')->get($userCacheKey);
            if (!$receiverUserData) {
                $receiverUserData = User::find($receiver);
                Cache::store('redis')->put($userCacheKey, $receiverUserData, now()->addHours(24));
            }
        } else {
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

Route::middleware([ 'throttle:global'])->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });
    
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    
    Route::middleware(['auth.cookie'])->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:chat');
    });

});
```

```bash
php artisan install:api
```

routes/api.php :
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthenticateWithCookie;
use App\Http\Middleware\CorsMiddleware;

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


Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:login',CorsMiddleware::class])->name('login.submit');
Route::middleware([AuthenticateWithCookie::class,'throttle:api'])->group(function () {
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
------------------------------------------------------------- Exception 
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

--------------------------------------------------------------------
App\Models\User.php :
```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    
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

bootstrap/app.php :
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

App\Providers\RouteServiceProvider.php :
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

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
}
```

bootstrap/providers.php :
```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
];
```

