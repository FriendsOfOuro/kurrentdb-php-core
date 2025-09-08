<?php

namespace EventStore\ValueObjects\Util;

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
     *
     * @return bool
     */
    public static function classEquals($object_a, $object_b)
    {
        return \get_class($object_a) === \get_class($object_b);
    }

    /**
     * Returns full namespaced class as string.
     *
     * @return string
     */
    public static function getClassAsString($object)
    {
        return \get_class($object);
    }
}
