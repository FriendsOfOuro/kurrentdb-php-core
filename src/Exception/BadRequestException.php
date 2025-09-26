<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

/**
 * Exception thrown for HTTP 400 Bad Request errors.
 *
 * This indicates a malformed or invalid request that cannot be processed.
 */
final class BadRequestException extends EventStoreException
{
}
