<?php

namespace EventStore\ValueObjects\StringLiteral;

use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;

abstract readonly class StringLiteral implements ValueObjectInterface
{
    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     */
    abstract public static function fromNative(string $value): static;

    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     */
    public function __construct(protected string $value)
    {
    }

    /**
     * Returns the value of the string.
     */
    public function toNative(): string
    {
        return $this->value;
    }

    /**
     * Tells whether two string literals are equal by comparing their values.
     */
    public function sameValueAs(ValueObjectInterface $stringLiteral): bool
    {
        if (false === Util::classEquals($this, $stringLiteral)) {
            return false;
        }

        return $this->toNative() === $stringLiteral->toNative();
    }

    /**
     * Tells whether the StringLiteral is empty.
     */
    public function isEmpty(): bool
    {
        return 0 == \strlen($this->toNative());
    }

    /**
     * Returns the string value itself.
     */
    public function __toString(): string
    {
        return $this->toNative();
    }
}
