<?php

namespace KurrentDB\Tests;

use KurrentDB\Exception\InvalidWritableEventObjectException;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class WritableEventCollectionTest.
 */
class WritableEventCollectionTest extends TestCase
{
    #[Test]
    public function event_collection_is_converted_to_stream_data(): void
    {
        $uuid1 = new UUID();
        $event1 = new WritableEvent($uuid1, 'Foo', ['bar']);

        $uuid2 = new UUID();
        $event2 = new WritableEvent($uuid2, 'Baz', ['foo']);

        $eventCollection = new WritableEventCollection([$event1, $event2]);

        $streamData = [
            [
                'eventId' => $uuid1->toNative(),
                'eventType' => 'Foo',
                'data' => ['bar'],
                'metadata' => [],
            ], [
                'eventId' => $uuid2->toNative(),
                'eventType' => 'Baz',
                'data' => ['foo'],
                'metadata' => [],
            ],
        ];

        $this->assertEquals($streamData, $eventCollection->toStreamData());
    }

    #[Test]
    public function invalid_collection_throws_exception(): void
    {
        $this->expectException(InvalidWritableEventObjectException::class);
        new WritableEventCollection(['foobar']);
    }
}
