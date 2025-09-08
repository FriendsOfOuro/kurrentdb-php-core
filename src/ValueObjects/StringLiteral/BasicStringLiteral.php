<?php

namespace EventStore\ValueObjects\StringLiteral;

final readonly class BasicStringLiteral extends StringLiteral
{
    public static function fromNative(string $value): static
    {
        return new self($value);
    }
}
