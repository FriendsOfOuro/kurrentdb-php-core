<?php
namespace EventStore;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class StreamDeletion.
 *
 * @static @method StreamDeletion SOFT()
 * @static @method StreamDeletion HARD()
 */
final class StreamDeletion extends Enum
{
    public const SOFT = 'soft';
    public const HARD = 'hard';
}
