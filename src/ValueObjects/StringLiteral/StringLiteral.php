<?php

declare(strict_types=1);

namespace KurrentDB\ValueObjects\StringLiteral;

use KurrentDB\ValueObjects\Util\Util;
use KurrentDB\ValueObjects\ValueObjectInterface;

abstract readonly class StringLiteral implements ValueObjectInterface
{
    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     */
    public function __construct(protected string $value)
    {
    }

    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     */
    abstract public static function fromNative(string $value): static;

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
    public function sameValueAs(ValueObjectInterface $object): bool
    {
        if (false === Util::classEquals($this, $object)) {
            return false;
        }

        return $this->toNative() === $object->toNative();
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
