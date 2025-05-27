<?php

namespace Tests\Feature\Broadcast;

use Tests\TestCase;
use App\Events\NewMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Database\Seeders\UserSeeder;

class NewMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed users (id=1, id=2)
    }

    public function test_new_message_event_broadcasts_correctly()
    {
        // Mock broadcasting queue
        Broadcast::shouldReceive('queue')
            ->once()
            ->andReturnUsing(function ($event) {
                $this->assertInstanceOf(NewMessage::class, $event);
                $this->assertEquals('private-message-box.2', $event->broadcastOn()->name);
                $this->assertEquals([
                    'sender_id' => 1,
                    'receiver_id' => 2,
                    'message' => 'Hello from server!',
                ], $event->broadcastWith());
            });

        // Trigger event
        event(new NewMessage(1, 2, 'Hello from server!'));

        $this->assertTrue(true);
    }

    public function test_new_message_event_is_dispatched_correctly()
    {
        // Fake event dispatching
        Event::fake([NewMessage::class]);

        // Trigger event
        event(new NewMessage(1, 2, 'Unit test message'));

        // Assert event was dispatched
        Event::assertDispatched(NewMessage::class, function ($event) {
            return $event->sender_id === 1 &&
                   $event->receiver_id === 2 &&
                   $event->message === 'Unit test message' &&
                   $event->broadcastOn()->name === 'private-message-box.2' &&
                   $event->broadcastWith() === [
                       'sender_id' => 1,
                       'receiver_id' => 2,
                       'message' => 'Unit test message',
                   ];
        });
    }
}