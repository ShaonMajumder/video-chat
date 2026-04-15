<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    protected const OFFER_TTL_SECONDS = 8;
    protected const CALL_CACHE_MINUTES = 1;
    protected const ENDED_CALL_TTL_SECONDS = 10;

    public function dashboard(Request $request)
    {
        return view('app.dashboard');
    }

    public function chatHub(Request $request)
    {
        return view('app.chat-hub');
    }

    public function conversation(Request $request, User $user)
    {
        abort_if($request->get('user')->id === $user->id, 404);

        return view('app.chat-call', [
            'selectedPeerId' => $user->id,
        ]);
    }

    public function profile(Request $request)
    {
        return view('app.profile');
    }

    public function settings(Request $request)
    {
        return view('app.settings');
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $sender = $request->get('user');
        $payload = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        abort_if((int) $payload['receiver_id'] === $sender->id, 422, 'Peer is invalid.');

        $message = trim(strip_tags($payload['message']));

        if ($message === '') {
            return response()->json(['message' => 'Message cannot be empty.'], 422);
        }

        broadcast(new NewMessage($sender->id, (int) $payload['receiver_id'], $message))->toOthers();

        return response()->json([
            'status' => 'sent',
            'message' => $message,
            'sender_id' => $sender->id,
            'receiver_id' => (int) $payload['receiver_id'],
        ]);
    }

    public function callState(Request $request): JsonResponse
    {
        $peerId = $this->validatedPeerId($request);
        $key = $this->callKey($request->get('user')->id, $peerId);
        $state = $this->pruneExpiredCallState(Cache::get($key, []));

        if ($state === []) {
            Cache::forget($key);
        } else {
            Cache::put($key, $state, now()->addMinutes(self::CALL_CACHE_MINUTES));
        }

        return response()->json([
            'call' => $state,
        ]);
    }

    public function createOffer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
            'sdp' => ['required', 'array'],
        ]);

        return $this->storeCallState($request, (int) $payload['peer_id'], function (array $state, int $userId) use ($payload) {
            $state['offer'] = [
                'from' => $userId,
                'sdp' => $payload['sdp'],
                'updated_at' => now()->toIso8601String(),
            ];

            unset($state['answer']);
            $state['candidates'] = [];
            $state['ended_at'] = null;

            return $state;
        });
    }

    public function createAnswer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
            'sdp' => ['required', 'array'],
        ]);

        return $this->storeCallState($request, (int) $payload['peer_id'], function (array $state, int $userId) use ($payload) {
            $state['answer'] = [
                'from' => $userId,
                'sdp' => $payload['sdp'],
                'updated_at' => now()->toIso8601String(),
            ];

            return $state;
        });
    }

    public function addCandidate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
            'candidate' => ['required', 'array'],
        ]);

        return $this->storeCallState($request, (int) $payload['peer_id'], function (array $state, int $userId) use ($payload) {
            $state['candidates'] ??= [];
            $state['candidates'][$userId] ??= [];
            $state['candidates'][$userId][] = $payload['candidate'];

            return $state;
        });
    }

    public function endCall(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return $this->storeCallState($request, (int) $payload['peer_id'], function (array $state) {
            $state['ended_at'] = now()->toIso8601String();
            $state['offer'] = null;
            $state['answer'] = null;
            $state['candidates'] = [];

            return $state;
        });
    }

    protected function storeCallState(Request $request, int $peerId, callable $mutator): JsonResponse
    {
        $userId = $request->get('user')->id;
        abort_if($peerId === $userId, 422, 'Peer is invalid.');

        $key = $this->callKey($userId, $peerId);
        $state = $this->pruneExpiredCallState(Cache::get($key, [
            'participants' => [$userId, $peerId],
            'offer' => null,
            'answer' => null,
            'candidates' => [],
            'ended_at' => null,
            'updated_at' => null,
        ]));

        $state = $mutator($state, $userId);
        $state['participants'] = [$userId, $peerId];
        $state['updated_at'] = now()->toIso8601String();

        Cache::put($key, $state, now()->addMinutes(self::CALL_CACHE_MINUTES));

        return response()->json(['call' => $state]);
    }

    protected function validatedPeerId(Request $request): int
    {
        $payload = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $peerId = (int) $payload['peer_id'];
        abort_if($peerId === $request->get('user')->id, 422, 'Peer is invalid.');

        return $peerId;
    }

    protected function callKey(int $a, int $b): string
    {
        [$low, $high] = collect([$a, $b])->sort()->values()->all();

        return "call:{$low}:{$high}";
    }

    protected function pruneExpiredCallState(array $state): array
    {
        if ($state === []) {
            return [];
        }

        $offerUpdatedAt = data_get($state, 'offer.updated_at');
        $hasAnswer = !empty($state['answer']);
        $hasEnded = !empty($state['ended_at']);

        if ($hasEnded) {
            $endedAt = data_get($state, 'ended_at');

            if (!$endedAt) {
                return [];
            }

            try {
                $endedAgeSeconds = Carbon::parse($endedAt)->diffInSeconds(now());
            } catch (\Throwable) {
                $endedAgeSeconds = self::ENDED_CALL_TTL_SECONDS + 1;
            }

            return $endedAgeSeconds <= self::ENDED_CALL_TTL_SECONDS ? $state : [];
        }

        if (!$offerUpdatedAt || $hasAnswer) {
            return $state;
        }

        try {
            $offerAgeSeconds = Carbon::parse($offerUpdatedAt)->diffInSeconds(now());
        } catch (\Throwable) {
            $offerAgeSeconds = self::OFFER_TTL_SECONDS + 1;
        }

        if ($offerAgeSeconds <= self::OFFER_TTL_SECONDS) {
            return $state;
        }

        return [];
    }
}
