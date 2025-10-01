<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\Exception\InvalidWritableEventObjectException;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use KurrentDB\WritableEventNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class WritableEventCollectionTest.
 */
class WritableEventCollectionTest extends TestCase
{
    #[Test]
    public function event_collection_is_serialized_to_stream_data(): void
    {
        $uuid1 = new UUID();
        $event1 = new WritableEvent($uuid1, 'Foo', ['data' => 'bar']);

        $uuid2 = new UUID();
        $event2 = new WritableEvent($uuid2, 'Baz', ['data' => 'foo']);

        $eventCollection = new WritableEventCollection([$event1, $event2]);

        $writableEventNormalizer = new WritableEventNormalizer();
        $serializer = new Serializer([$writableEventNormalizer, new ObjectNormalizer()], [new JsonEncoder()]);

        $serialized = $serializer->serialize($eventCollection->getEvents(), 'json');

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

    #[Test]
    public function invalid_collection_throws_exception(): void
    {
        $this->expectException(InvalidWritableEventObjectException::class);
        // @phpstan-ignore-next-line
        new WritableEventCollection(['invalid']);
    }
}
