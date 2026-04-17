<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSignalUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $participants,
        public array $call,
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId) => new PrivateChannel('call-signaling.' . $userId),
            $this->participants
        );
    }

    public function broadcastAs(): string
    {
        return 'call.signal.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'participants' => $this->participants,
            'call' => $this->call,
        ];
    }
}
