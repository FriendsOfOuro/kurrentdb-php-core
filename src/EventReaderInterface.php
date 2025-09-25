<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\Event;
use Psr\Http\Message\UriInterface;

/**
 * Interface for event reading operations.
 */
interface EventReaderInterface
{
    /**
     * Read a single event.
     *
     * @param UriInterface $eventUri The url of the event
     */
    public function readEvent(UriInterface $eventUri): Event;

    /**
     * Reads a batch of events.
     *
     * @param UriInterface[] $eventUrls The urls of the events
     *
     * @return Event[]
     */
    public function readEventBatch(array $eventUrls): array;
}
