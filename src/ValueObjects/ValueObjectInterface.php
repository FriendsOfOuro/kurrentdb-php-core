<?php

declare(strict_types=1);

namespace KurrentDB\ValueObjects;

interface ValueObjectInterface extends \Stringable
{
    /**
     * Compare two ValueObjectInterface and tells whether they can be considered equal.
     */
    public function sameValueAs(ValueObjectInterface $object): bool;

    public function toNative(): mixed;
}
