<?php

declare(strict_types=1);

namespace KurrentDB\ValueObjects\Util;

/**
 * Utility class for methods used all across the library.
 */
class Util
{
    /**
     * Tells whether two objects are of the same class.
     *
     * @param object $object_a
     * @param object $object_b
     */
    public static function classEquals($object_a, $object_b): bool
    {
        return $object_a::class === $object_b::class;
    }

    /**
     * Returns full namespaced class as string.
     */
    public static function getClassAsString(object $object): string
    {
        return $object::class;
    }
}
