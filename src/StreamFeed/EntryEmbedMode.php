<?php

namespace EventStore\StreamFeed;

enum EntryEmbedMode: string
{
    case NONE = 'none';
    case RICH = 'rich';
    case BODY = 'body';
}
