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
    public const string FIRST = 'first';
    public const string LAST = 'last';
    public const string PREVIOUS = 'previous';
    public const string NEXT = 'next';
    public const string METADATA = 'metadata';
    public const string ALTERNATE = 'alternate';
}
