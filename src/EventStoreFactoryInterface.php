<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\Exception\ConnectionFailedException;

interface EventStoreFactoryInterface
{
    /**
     * @throws ConnectionFailedException
     */
    public function create(): EventStore;
}
