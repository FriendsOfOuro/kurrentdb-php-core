<?php

namespace KurrentDB\StreamFeed;

enum EntryEmbedMode: string
{
    case NONE = 'none';
    case RICH = 'rich';
    case BODY = 'body';
}
