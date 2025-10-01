<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

interface StreamFeedFactoryInterface
{
    /**
     * @param array<string, mixed> $json
     */
    public function create(
        array $json,
        EntryEmbedMode $embedMode = EntryEmbedMode::NONE,
    ): StreamFeed;
}
