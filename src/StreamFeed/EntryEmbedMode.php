<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

enum EntryEmbedMode: string
{
    case NONE = 'none';
    case RICH = 'rich';
    case BODY = 'body';
}
