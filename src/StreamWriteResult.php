<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Value object representing the result of writing to a stream.
 */
final readonly class StreamWriteResult
{
    public function __construct(
        public int $version,
    ) {
    }
}
