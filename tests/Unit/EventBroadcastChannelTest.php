<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit\Framework\Attributes\Test;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class EventBroadcastChannelTest extends TestCase
{
    /** @test */
    public function it_asserts_event_channel_type()
    {
        $publicEvent = new class implements ShouldBroadcast {
            public function broadcastOn() { return new Channel('test'); }
        };

        $this->assertEventChannelType('public', $publicEvent);
    }

    /** @test */
    public function it_asserts_dont_broadcast_to_current_user()
    {
        // Mocking behavior normally handled by Laravel's InteractsWithSockets
        $event = new class implements ShouldBroadcast {
            public $socket;
            public function dontBroadcastToCurrentUser() { $this->socket = 'socket-id'; return $this; }

            public function broadcastOn() { return new Channel('test'); }
        };

        // Simulate logic
        $event->dontBroadcastToCurrentUser();

        // Pass 'socket-id' as the expected value
        $this->assertDontBroadcastToCurrentUser($event, 'socket-id');
    }
}