<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\StreamFeed;

use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\EventDenormalizer;
use KurrentDB\ValueObjects\Identity\UUID;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventDenormalizerTest extends TestCase
{
    private EventDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new EventDenormalizer();
    }

    #[Test]
    public function it_denormalizes_event_with_all_fields(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
            'metadata' => ['user' => 'test'],
            'eventId' => 'b2d506fd-409d-4ec7-b02f-c6aedb1f4124',
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertSame('TestEvent', $event->getType());
        $this->assertSame(42, $event->getVersion());
        $this->assertSame(['foo' => 'bar'], $event->getData());
        $this->assertSame(['user' => 'test'], $event->getMetadata());
        $this->assertInstanceOf(UUID::class, $event->getEventId());
        $this->assertSame('b2d506fd-409d-4ec7-b02f-c6aedb1f4124', $event->getEventId()->toNative());
    }

    #[Test]
    public function it_handles_empty_string_metadata(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
            'metadata' => '',
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertNull($event->getMetadata());
    }

    #[Test]
    public function it_handles_empty_array_metadata(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
            'metadata' => [],
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertNull($event->getMetadata());
    }

    #[Test]
    public function it_handles_missing_metadata(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertNull($event->getMetadata());
    }

    #[Test]
    public function it_handles_empty_string_event_id(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
            'eventId' => '',
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertNull($event->getEventId());
    }

    #[Test]
    public function it_handles_missing_event_id(): void
    {
        $data = [
            'eventType' => 'TestEvent',
            'eventNumber' => 42,
            'data' => ['foo' => 'bar'],
        ];

        $event = $this->denormalizer->denormalize($data, Event::class);

        $this->assertNull($event->getEventId());
    }

    #[Test]
    public function it_supports_denormalization_for_event_class(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], Event::class));
    }

    #[Test]
    public function it_does_not_support_other_classes(): void
    {
        $this->assertFalse($this->denormalizer->supportsDenormalization([], \stdClass::class));
    }

    #[Test]
    public function it_returns_supported_types(): void
    {
        $types = $this->denormalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(Event::class, $types);
        $this->assertTrue($types[Event::class]);
    }
}
