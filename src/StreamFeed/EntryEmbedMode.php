<?php

namespace EventStore\StreamFeed;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class EntryEmbedMode.
 *
 * @method static EntryEmbedMode NONE()
 * @method static EntryEmbedMode RICH()
 * @method static EntryEmbedMode BODY()
 */
final class EntryEmbedMode extends Enum
{
    public const NONE = 'none';
    public const RICH = 'rich';
    public const BODY = 'body';
}
