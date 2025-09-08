<?php

namespace EventStore;

/**
 * Interface WritableToStream.
 */
interface WritableToStream
{
    /**
     * @return array
     */
    public function toStreamData();
}
