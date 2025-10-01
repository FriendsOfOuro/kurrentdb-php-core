<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

/**
 * Class StreamFeed.
 */
final readonly class StreamFeed
{
    use HasLinks;

    /**
     * @param Link[]               $links
     * @param Entry[]              $entries
     * @param array<string, mixed> $json
     */
    public function __construct(private array $links, private array $entries, private array $json, private EntryEmbedMode $entryEmbedMode)
    {
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getEntryEmbedMode(): EntryEmbedMode
    {
        return $this->entryEmbedMode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @return Link[]
     */
    protected function getLinks(): array
    {
        return $this->links;
    }
}
