<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Interface WritableToStream.
 */
interface WritableToStream
{
    /**
     * @return array<string, mixed>|array<array<string, mixed>>
     */
    public function toStreamData(): array;
}
