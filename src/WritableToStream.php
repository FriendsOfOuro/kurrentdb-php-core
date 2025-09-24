<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Interface WritableToStream.
 */
interface WritableToStream
{
    public function toStreamData(): array;
}
