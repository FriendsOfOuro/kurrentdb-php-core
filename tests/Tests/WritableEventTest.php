<?php

namespace KurrentDB\Tests;

use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class WritableEventTest.
 */
class WritableEventTest extends TestCase
{
    #[Test]
    public function event_is_converted_to_stream_data(): void
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
