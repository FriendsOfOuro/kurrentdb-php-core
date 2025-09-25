<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

enum LinkRelation: string
{
    case FIRST = 'first';
    case LAST = 'last';
    case PREVIOUS = 'previous';
    case NEXT = 'next';
    case METADATA = 'metadata';
    case ALTERNATE = 'alternate';
    case SELF = 'self';
    case EDIT = 'edit';
}
