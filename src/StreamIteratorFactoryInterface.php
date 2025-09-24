<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\StreamFeed\StreamFeedIterator;

/**
 * Interface for stream iterator factory operations.
 */
interface StreamIteratorFactoryInterface
{
    /**
     * Create a forward stream feed iterator.
     *
     * @param string $streamName The stream name
     * @param int    $pageLimit  Maximum number of pages to iterate
     */
    public function forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator;

    /**
     * Create a backward stream feed iterator.
     *
     * @param string $streamName The stream name
     * @param int    $pageLimit  Maximum number of pages to iterate
     */
    public function backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator;
}
