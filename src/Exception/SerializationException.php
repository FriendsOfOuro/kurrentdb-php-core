<?php

declare(strict_types=1);

namespace KurrentDB\Exception;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SymfonySerializerException;

/**
 * Thrown when event serialization fails.
 *
 * This indicates a developer error - invalid data structure passed to the serializer.
 */
final class SerializationException extends \InvalidArgumentException
{
    public static function fromSymfonyException(SymfonySerializerException $previous): self
    {
        return new self(
            sprintf('Failed to serialize event data: %s', $previous->getMessage()),
            0,
            $previous
        );
    }
}
