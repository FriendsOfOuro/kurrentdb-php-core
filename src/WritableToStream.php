<?php

namespace EventStore;

/**
 * Interface WritableToStream.
 */
interface WritableToStream
{
    public function toStreamData(): array;
}
