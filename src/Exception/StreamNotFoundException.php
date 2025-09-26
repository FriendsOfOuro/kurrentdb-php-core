<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

/**
 * Exception thrown when a stream is not found (HTTP 404).
 *
 * This could mean the stream never existed or was soft deleted.
 */
final class StreamNotFoundException extends StreamNotAvailableException
{
}
