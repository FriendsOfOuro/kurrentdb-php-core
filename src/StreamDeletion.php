<?php

namespace KurrentDB;

enum StreamDeletion: string
{
    case SOFT = 'soft';
    case HARD = 'hard';
}
