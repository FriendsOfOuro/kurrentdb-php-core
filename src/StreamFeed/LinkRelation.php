<?php

namespace EventStore\StreamFeed;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class LinkRelation.
 *
 * @method static LinkRelation FIRST()
 * @method static LinkRelation LAST()
 * @method static LinkRelation PREVIOUS()
 * @method static LinkRelation NEXT()
 * @method static LinkRelation METADATA()
 * @method static LinkRelation ALTERNATE()
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
