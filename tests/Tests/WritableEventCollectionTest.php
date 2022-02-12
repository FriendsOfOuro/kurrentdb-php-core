<?php
namespace EventStore\Tests;

use EventStore\Exception\InvalidWritableEventObjectException;
use EventStore\ValueObjects\Identity\UUID;
use EventStore\WritableEvent;
use EventStore\WritableEventCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class WritableEventCollectionTest.
 */
class WritableEventCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function event_collection_is_converted_to_stream_data()
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

    /**
     * @test
     */
    public function invalid_collection_throws_exception()
    {
        $this->expectException(InvalidWritableEventObjectException::class);
        new WritableEventCollection(['foobar']);
    }
}
