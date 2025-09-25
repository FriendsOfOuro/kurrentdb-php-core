<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\ValueObjects\Identity\UUID;

/**
 * Class WritableEvent.
 */
final readonly class WritableEvent implements WritableToStream
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     */
    public static function newInstance(string $type, array $data, array $metadata = []): self
    {
        return new self(new UUID(), $type, $data, $metadata);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     */
    public function __construct(private UUID $uuid, private string $type, private array $data, private array $metadata = [])
    {
    }

    /**
     * @return array{eventId: string, eventType: string, data: array<string, mixed>, metadata: array<string, mixed>}
     */
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
