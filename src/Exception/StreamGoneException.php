<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

/**
 * Exception thrown when a stream has been permanently deleted (HTTP 410).
 *
 * This happens when a stream was hard deleted and is permanently gone.
 */
final class StreamGoneException extends StreamNotAvailableException
{
}
