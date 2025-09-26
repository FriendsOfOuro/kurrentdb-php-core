<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

/**
 * Base class for exceptions when a stream is not available.
 *
 * This covers both cases where the stream doesn't exist (404)
 * and where it has been permanently deleted (410).
 */
abstract class StreamNotAvailableException extends StreamException
{
}
