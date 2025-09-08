<?php

namespace EventStore;

use EventStore\ValueObjects\Enum\Enum;

/**
 * Class StreamDeletion.
 *
 * @method static StreamDeletion SOFT()
 * @method static StreamDeletion HARD()
 */
final class StreamDeletion extends Enum
{
    public const string SOFT = 'soft';
    public const string HARD = 'hard';
}
