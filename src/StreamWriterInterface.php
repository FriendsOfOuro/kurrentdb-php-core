<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Interface for stream writing operations.
 */
interface StreamWriterInterface
{
    /**
     * Write one or more events to a stream.
     *
     * @param string                $streamName        The stream name
     * @param WritableToStream      $events            Single event or a collection of events
     * @param int                   $expectedVersion   The expected version of the stream
     * @param array<string, string> $additionalHeaders Additional HTTP headers
     *
     * @throws Exception\BadRequestException
     * @throws Exception\ConnectionFailedException
     * @throws Exception\NoExtractableEventVersionException
     * @throws Exception\StreamGoneException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     * @throws Exception\WrongExpectedVersionException
     */
    public function writeToStream(string $streamName, WritableToStream $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): StreamWriteResult;

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     *
     * @throws Exception\BadRequestException
     * @throws Exception\StreamGoneException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     * @throws Exception\WrongExpectedVersionException
     */
    public function deleteStream(string $streamName, StreamDeletion $mode): void;
}
