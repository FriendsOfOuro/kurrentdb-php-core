<?php
namespace EventStore\StreamFeed;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class EntryEmbedMode.
 *
 * @static @method EntryEmbedMode NONE()
 * @static @method EntryEmbedMode RICH()
 * @static @method EntryEmbedMode BODY()
 */
final class EntryEmbedMode extends Enum
{
    public const NONE = 'none';
    public const RICH = 'rich';
    public const BODY = 'body';
}
