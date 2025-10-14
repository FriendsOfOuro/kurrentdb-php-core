<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\Tests\SerializerFactory;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

/**
 * Class WritableEventCollectionTest.
 */
class WritableEventCollectionTest extends TestCase
{
    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function event_collection_is_serialized_to_stream_data(): void
    {
        $uuid1 = new UUID();
        $event1 = new WritableEvent($uuid1, 'Foo', ['data' => 'bar']);

        $uuid2 = new UUID();
        $event2 = new WritableEvent($uuid2, 'Baz', ['data' => 'foo']);

        $eventCollection = WritableEventCollection::of($event1, $event2);

        $serializer = SerializerFactory::create();
        $serialized = $serializer->serialize($eventCollection->events, 'json');

        $expected = json_encode([
            [
                'eventId' => $uuid1->toNative(),
                'eventType' => 'Foo',
                'data' => ['data' => 'bar'],
                'metadata' => [],
            ],
            [
                'eventId' => $uuid2->toNative(),
                'eventType' => 'Baz',
                'data' => ['data' => 'foo'],
                'metadata' => [],
            ],
        ]);

        $this->assertEquals($expected, $serialized);
    }
}
