<?php
namespace EventStore\ValueObjects;

interface ValueObjectInterface
{
    /**
     * Returns a object taking PHP native value(s) as argument(s).
     *
     * @return ValueObjectInterface
     */
    public static function fromNative();

    /**
     * Compare two ValueObjectInterface and tells whether they can be considered equal.
     *
     * @return bool
     */
    public function sameValueAs(ValueObjectInterface $object);

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function __toString();
}
