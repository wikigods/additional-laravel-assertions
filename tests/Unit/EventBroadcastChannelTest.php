<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Broadcast;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class EventBroadcastChannelTest extends TestCase
{
    #[Test]
    public function it_passes_if_event_does_not_broadcast_to_current_user()
    {
        Broadcast::shouldReceive('socket')->andReturn('socket-id');

        $event = new FakePublicEvent();

        $this->assertDontBroadcastToCurrentUser($event);
    }

    #[Test]
    public function it_fails_if_event_broadcasts_to_current_user()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The event ' . FakePrivateEvent::class .
            ' must call the method "dontBroadcastToCurrentUser" in the constructor.'
        );

        Broadcast::shouldReceive('socket')->andReturn('socket-id');

        $event = new FakePrivateEvent();

        $this->assertDontBroadcastToCurrentUser($event);
    }

    #[Test]
    public function it_passes_for_public_channel()
    {
        $event = new FakePublicEvent();

        $this->assertEventChannelType('public', $event);
    }

    #[Test]
    public function it_passes_for_private_channel()
    {
        $event = new FakePrivateEvent();

        $this->assertEventChannelType('private', $event);
    }

    #[Test]
    public function it_passes_for_presence_channel()
    {
        $event = new FakePresenceEvent();

        $this->assertEventChannelType('presence', $event);
    }

    #[Test]
    public function it_fails_if_channel_type_does_not_match()
    {
        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage(
            "The channel type 'wrong-channel' is not valid. Valid types are: public, private, presence"
        );

        $event = new FakePublicEvent();

        $this->assertEventChannelType('wrong-channel', $event);
    }

    #[Test]
    public function it_passes_if_event_has_expected_channel_name()
    {
        $event = new FakePublicEvent();

        $this->assertEventChannelName('public-channel', $event);
    }

    #[Test]
    public function it_fails_if_event_has_different_channel_name()
    {
        $this->expectException(ExpectationFailedException::class);

        $event = new FakePublicEvent();

        $this->assertEventChannelName('private-channel', $event);
    }
}

class FakePublicEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct()
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('public-channel');
    }
}

class FakePrivateEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct()
    {

    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('private-channel');
    }
}

class FakePresenceEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct()
    {

    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('presence-channel');
    }
}