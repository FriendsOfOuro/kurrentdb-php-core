<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\Event;

/**
 * Interface for event reading operations.
 */
interface EventReaderInterface
{
    /**
     * Read a single event.
     *
     * @param string $eventUrl The url of the event
     */
    public function readEvent(string $eventUrl): Event;

    /**
     * Reads a batch of events.
     *
     * @return Event[]
     */
    public function readEventBatch(array $eventUrls): array;
}
