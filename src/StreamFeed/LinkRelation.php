<?php
namespace EventStore\StreamFeed;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class LinkRelation.
 *
 * @static @method LinkRelation FIRST()
 * @static @method LinkRelation LAST()
 * @static @method LinkRelation PREVIOUS()
 * @static @method LinkRelation NEXT()
 * @static @method LinkRelation METADATA()
 * @static @method LinkRelation ALTERNATE()
 */
final class LinkRelation extends Enum
{
    public const FIRST = 'first';
    public const LAST = 'last';
    public const PREVIOUS = 'previous';
    public const NEXT = 'next';
    public const METADATA = 'metadata';
    public const ALTERNATE = 'alternate';
}
