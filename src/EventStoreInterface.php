<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Main EventStore interface that combines all EventStore operations.
 *
 * This interface extends all specialized interfaces to maintain backward compatibility
 * while allowing clients to depend on more focused interfaces as needed.
 */
interface EventStoreInterface extends StreamReaderInterface, StreamWriterInterface, StreamIteratorFactoryInterface
{
}
