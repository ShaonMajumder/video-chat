<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return view('chat');
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
        $state = Cache::get($this->callKey($request->get('user')->id, $peerId), []);

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
        $state = Cache::get($key, [
            'participants' => [$userId, $peerId],
            'offer' => null,
            'answer' => null,
            'candidates' => [],
            'ended_at' => null,
            'updated_at' => null,
        ]);

        $state = $mutator($state, $userId);
        $state['participants'] = [$userId, $peerId];
        $state['updated_at'] = now()->toIso8601String();

        Cache::put($key, $state, now()->addMinutes(15));

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
}
