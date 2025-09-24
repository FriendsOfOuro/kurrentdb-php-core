<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\ValueObjects\Identity\UUID;

/**
 * Class WritableEvent.
 */
final readonly class WritableEvent implements WritableToStream
{
    public static function newInstance(string $type, array $data, array $metadata = []): self
    {
        return new self(new UUID(), $type, $data, $metadata);
    }

    public function __construct(private UUID $uuid, private string $type, private array $data, private array $metadata = [])
    {
    }

    public function toStreamData(): array
    {
        return [
            'eventId' => $this->uuid->toNative(),
            'eventType' => $this->type,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
