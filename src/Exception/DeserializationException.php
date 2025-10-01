<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SymfonySerializerException;

/**
 * Thrown when event deserialization fails.
 *
 * This indicates an unexpected response format from the server.
 */
final class DeserializationException extends \UnexpectedValueException
{
    public static function fromSymfonyException(SymfonySerializerException $previous): self
    {
        return new self(
            sprintf('Failed to deserialize server response: %s', $previous->getMessage()),
            0,
            $previous
        );
    }
}
