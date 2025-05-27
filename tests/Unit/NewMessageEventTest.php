<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Events\NewMessage;
use Illuminate\Broadcasting\PrivateChannel;

class NewMessageEventTest extends TestCase
{
    /** @test */
    public function new_message_event_broadcasts_on_correct_channel()
    {
        $event = new NewMessage(1, 2, 'Hello World');
        $channels = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channels);
        $this->assertEquals('private-message-box.2', $channels->name); // Include 'private-' prefix
    }

    /** @test */
    public function new_message_event_broadcasts_correct_data()
    {
        $event = new NewMessage(1, 2, 'Hello World');
        $data = $event->broadcastWith();

        $this->assertEquals([
            'sender_id' => 1,
            'receiver_id' => 2,
            'message' => 'Hello World',
        ], $data);
    }
}
