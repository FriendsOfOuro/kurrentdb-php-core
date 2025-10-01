<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class WritableEventNormalizerTest.
 */
class WritableEventNormalizerTest extends TestCase
{
    #[Test]
    public function it_normalizes_event_to_stream_data(): void
    {
        $uuid = new UUID();
        $event = new WritableEvent($uuid, 'Foo', ['data' => 'bar']);

        $writableEventNormalizer = new WritableEventNormalizer();
        $serializer = new Serializer([$writableEventNormalizer, new ObjectNormalizer()], [new JsonEncoder()]);

        $normalized = $serializer->normalize($event);

        $expected = [
            'eventId' => $uuid->toNative(),
            'eventType' => 'Foo',
            'data' => ['data' => 'bar'],
            'metadata' => [],
        ];

        $this->assertEquals($expected, $normalized);
    }
}
