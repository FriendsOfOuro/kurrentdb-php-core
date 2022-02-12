<?php
namespace EventStore;

use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use EventStore\StreamFeed\StreamFeedIterator;
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
     *
     * @return StreamFeed|null
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation);

    /**
     * Get the response from the last HTTP call to the EventStore API.
     *
     * @return ResponseInterface
     */
    public function getLastResponse();

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
    public function writeToStream($streamName, WritableToStream $events, $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders);

    /**
     * Read a single event.
     *
     * @param string $eventUrl The url of the event
     *
     * @return Event
     */
    public function readEvent($eventUrl);

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream($streamName, StreamDeletion $mode);

    /**
     * Open a stream feed for read and navigation.
     *
     * @param string         $streamName The stream name
     * @param EntryEmbedMode $embedMode  The event entries embed mode (none, rich or body)
     *
     * @return StreamFeed
     */
    public function openStreamFeed($streamName, EntryEmbedMode $embedMode = null);

    /**
     * @param string $streamName
     *
     * @return StreamFeedIterator
     */
    public function forwardStreamFeedIterator($streamName);

    /**
     * @param string $streamName
     *
     * @return StreamFeedIterator
     */
    public function backwardStreamFeedIterator($streamName);
}
