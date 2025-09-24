<?php

declare(strict_types=1);

namespace KurrentDB;

enum StreamDeletion: string
{
    case SOFT = 'soft';
    case HARD = 'hard';
}
