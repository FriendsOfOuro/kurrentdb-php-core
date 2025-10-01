<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

        $normalizer = new WritableEventNormalizer();
        $normalized = $normalizer->normalize($event);

        $expected = [
            'eventId' => $uuid->toNative(),
            'eventType' => 'Foo',
            'data' => ['data' => 'bar'],
            'metadata' => [],
        ];

        $this->assertEquals($expected, $normalized);
    }
}
