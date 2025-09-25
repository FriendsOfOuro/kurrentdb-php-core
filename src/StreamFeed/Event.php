<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\ValueObjects\Identity\UUID;

/**
 * Class Event.
 */
final readonly class Event
{
    /**
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $metadata
     */
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

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getEventId(): ?UUID
    {
        return $this->eventId;
    }
}
