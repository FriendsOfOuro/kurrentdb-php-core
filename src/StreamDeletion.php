<?php

namespace EventStore;

enum StreamDeletion: string
{
    case SOFT = 'soft';
    case HARD = 'hard';
}
