<?php

namespace EventStore\Tests;

use EventStore\ValueObjects\Identity\UUID;
use EventStore\WritableEvent;
use PHPUnit\Framework\TestCase;

/**
 * Class WritableEventTest.
 */
class WritableEventTest extends TestCase
{
    /**
     * @test
     */
    public function event_is_converted_to_stream_data()
    {
        $uuid = new UUID();
        $event = new WritableEvent($uuid, 'Foo', ['bar']);
        $streamData = [
            'eventId' => $uuid->toNative(),
            'eventType' => 'Foo',
            'data' => ['bar'],
            'metadata' => [],
        ];

        $this->assertEquals($streamData, $event->toStreamData());
    }
}
