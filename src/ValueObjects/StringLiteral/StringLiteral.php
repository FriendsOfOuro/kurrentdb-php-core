<?php

namespace EventStore\ValueObjects\StringLiteral;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;

class StringLiteral implements ValueObjectInterface
{
    protected $value;

    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     */
    public static function fromNative(): static
    {
        $value = func_get_arg(0);

        return new static($value);
    }

    /**
     * Returns a StringLiteral object given a PHP native string as parameter.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        if (false === \is_string($value)) {
            throw new InvalidNativeArgumentException($value, ['string']);
        }

        $this->value = $value;
    }

    /**
     * Returns the value of the string.
     *
     * @return string
     */
    public function toNative()
    {
        return $this->value;
    }

    /**
     * Tells whether two string literals are equal by comparing their values.
     *
     * @return bool
     */
    public function sameValueAs(ValueObjectInterface $stringLiteral)
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
