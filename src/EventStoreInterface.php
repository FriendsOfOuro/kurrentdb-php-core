<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedIterator;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface EventStoreInterface.
 */
interface EventStoreInterface
{
    /**
     * Navigate stream feed through link relations.
     *
     * @param StreamFeed   $streamFeed The stream feed to navigate through
     * @param LinkRelation $relation   The "direction" expressed as link relation
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed;

    /**
     * Get the response from the last HTTP call to the EventStore API.
     */
    public function getLastResponse(): ResponseInterface;

    /**
     * Write one or more events to a stream.
     *
     * @param string           $streamName        The stream name
     * @param WritableToStream $events            Single event or a collection of events
     * @param int              $expectedVersion   The expected version of the stream
     * @param array            $additionalHeaders Additional HTTP headers
     *
     * @throws Exception\WrongExpectedVersionException
     */
    public function writeToStream(string $streamName, WritableToStream $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): false|int;

    /**
     * Read a single event.
     *
     * @param string $eventUrl The url of the event
     */
    public function readEvent(string $eventUrl): Event;

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream(string $streamName, StreamDeletion $mode);

    /**
     * Open a stream feed for read and navigation.
     *
     * @param string         $streamName The stream name
     * @param EntryEmbedMode $embedMode  The event entries embed mode (none, rich or body)
     */
    public function openStreamFeed(string $streamName, EntryEmbedMode $embedMode = EntryEmbedMode::NONE): StreamFeed;

    public function forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator;

    public function backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator;

    /**
     * Reads a batch of events.
     *
     * @return Event[]
     */
    public function readEventBatch(array $eventUrls): array;
}
