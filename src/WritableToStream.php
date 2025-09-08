<?php

namespace KurrentDB;

/**
 * Interface WritableToStream.
 */
interface WritableToStream
{
    public function toStreamData(): array;
}
