<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\ValueObjects\Identity\UUID;

/**
 * Class WritableEvent.
 */
final readonly class WritableEvent
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
    public function __construct(public UUID $uuid, public string $type, public array $data, public array $metadata = [])
    {
    }
}
