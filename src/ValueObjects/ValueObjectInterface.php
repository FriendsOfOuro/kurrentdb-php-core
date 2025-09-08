<?php

namespace EventStore\ValueObjects;

interface ValueObjectInterface extends \Stringable
{
    /**
     * Compare two ValueObjectInterface and tells whether they can be considered equal.
     */
    public function sameValueAs(ValueObjectInterface $object): bool;

    public function toNative(): mixed;
}
