<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedIterator;
use Psr\Http\Message\UriInterface;

/**
 * EventStore facade that delegates to specialized services.
 *
 * This class acts as a facade over three specialized services:
 * - StreamReader: handles stream reading and navigation
 * - StreamWriter: handles stream writing and deletion
 * - StreamIteratorFactory: creates stream iterators
 */
final readonly class EventStore implements EventStoreInterface
{
    private StreamReaderInterface $streamReader;
    private StreamWriterInterface $streamWriter;
    private StreamIteratorFactoryInterface $streamIteratorFactory;

    public function __construct(
        StreamReaderInterface $streamReader,
        StreamWriterInterface $streamWriter,
        StreamIteratorFactoryInterface $streamIteratorFactory,
    ) {
        $this->streamReader = $streamReader;
        $this->streamWriter = $streamWriter;
        $this->streamIteratorFactory = $streamIteratorFactory;
    }

    // StreamReaderInterface methods - delegate to StreamReader

    public function openStreamFeed(string $streamName, EntryEmbedMode $embedMode = EntryEmbedMode::NONE): StreamFeed
    {
        return $this->streamReader->openStreamFeed($streamName, $embedMode);
    }

    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed
    {
        return $this->streamReader->navigateStreamFeed($streamFeed, $relation);
    }

    public function readEvent(UriInterface $eventUri): Event
    {
        return $this->streamReader->readEvent($eventUri);
    }

    public function readEventBatch(array $eventUrls): array
    {
        return $this->streamReader->readEventBatch($eventUrls);
    }

    // StreamWriterInterface methods - delegate to StreamWriter

    public function writeToStream(string $streamName, WritableEventCollection $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): StreamWriteResult
    {
        return $this->streamWriter->writeToStream($streamName, $events, $expectedVersion, $additionalHeaders);
    }

    public function deleteStream(string $streamName, StreamDeletion $mode): void
    {
        $this->streamWriter->deleteStream($streamName, $mode);
    }

    // StreamIteratorFactoryInterface methods - delegate to StreamIteratorFactory

    public function forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return $this->streamIteratorFactory->forwardStreamFeedIterator($streamName, $pageLimit);
    }

    public function backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return $this->streamIteratorFactory->backwardStreamFeedIterator($streamName, $pageLimit);
    }
}
