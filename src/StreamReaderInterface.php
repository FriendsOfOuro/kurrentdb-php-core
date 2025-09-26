<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;

/**
 * Interface for stream reading operations.
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
}
