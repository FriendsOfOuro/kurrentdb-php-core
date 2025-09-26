<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface for stream and event reading operations.
 *
 * This interface combines stream-level operations (feeds, navigation) with
 * event-level operations (individual and batch reading) since streams and
 * events are inseparable domain concepts.
 */
interface StreamReaderInterface
{
    /**
     * Open a stream feed for read and navigation.
     *
     * @param string         $streamName The stream name
     * @param EntryEmbedMode $embedMode  The event entries embed mode (none, rich or body)
     *
     * @throws Exception\BadRequestException
     * @throws Exception\StreamGoneException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     * @throws Exception\WrongExpectedVersionException
     */
    public function openStreamFeed(string $streamName, EntryEmbedMode $embedMode = EntryEmbedMode::NONE): StreamFeed;

    /**
     * Navigate stream feed through link relations.
     *
     * @param StreamFeed   $streamFeed The stream feed to navigate through
     * @param LinkRelation $relation   The "direction" expressed as link relation
     *
     * @throws Exception\BadRequestException
     * @throws Exception\StreamGoneException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     * @throws Exception\WrongExpectedVersionException
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed;

    /**
     * Read a single event.
     *
     * @param UriInterface $eventUri The url of the event
     *
     * @throws Exception\BadRequestException
     * @throws Exception\StreamGoneException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     * @throws Exception\WrongExpectedVersionException
     */
    public function readEvent(UriInterface $eventUri): Event;

    /**
     * Reads a batch of events.
     *
     * @param UriInterface[] $eventUrls The urls of the events
     *
     * @return Event[]
     *
     * @throws ClientExceptionInterface
     */
    public function readEventBatch(array $eventUrls): array;
}
