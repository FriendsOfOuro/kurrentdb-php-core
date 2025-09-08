<?php

namespace EventStore\StreamFeed;

use EventStore\ValueObjects\Identity\UUID;

/**
 * Class Event.
 */
final readonly class Event
{
    public function __construct(
        private string $type,
        private int $version,
        private array $data,
        private ?array $metadata = null,
        private ?UUID $eventId = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getEventId(): ?UUID
    {
        return $this->eventId;
    }
}
