<?php

namespace EventStore\ValueObjects\Enum;

use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;
use MabeEnum\Enum as BaseEnum;

abstract class Enum extends BaseEnum implements ValueObjectInterface
{
    /**
     * Returns the PHP native value of the enum.
     */
    public function toNative(): string
    {
        $value = parent::getValue();
        assert(is_scalar($value));

        return (string) $value;
    }

    /**
     * Tells whether two Enum objects are sameValueAs by comparing their values.
     *
     * @param Enum $enum
     */
    public function sameValueAs(ValueObjectInterface $enum): bool
    {
        if (false === Util::classEquals($this, $enum)) {
            return false;
        }

        return $this->toNative() === $enum->toNative();
    }

    /**
     * Returns a native string representation of the Enum value.
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->toNative();
    }
}
