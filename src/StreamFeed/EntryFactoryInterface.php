<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

interface EntryFactoryInterface
{
    /**
     * @param array<string, mixed> $json
     */
    public function create(array $json): Entry;
}
