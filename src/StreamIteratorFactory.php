<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\StreamFeedIterator;

final readonly class StreamIteratorFactory implements StreamIteratorFactoryInterface
{
    public function __construct(
        private StreamReaderInterface $streamReader,
    ) {
    }

    /**
     * Create a forward stream feed iterator.
     *
     * @param string $streamName The stream name
     * @param int    $pageLimit  Maximum number of pages to iterate
     */
    public function forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return StreamFeedIterator::forward($this->streamReader, $streamName, $pageLimit);
    }

    /**
     * Create a backward stream feed iterator.
     *
     * @param string $streamName The stream name
     * @param int    $pageLimit  Maximum number of pages to iterate
     */
    public function backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return StreamFeedIterator::backward($this->streamReader, $streamName, $pageLimit);
    }
}
