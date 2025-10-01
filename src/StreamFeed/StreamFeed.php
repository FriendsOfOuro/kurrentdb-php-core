<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;

/**
 * Stream feed representation (paginated list of entries).
 */
final readonly class StreamFeed
{
    /**
     * @param Link[]      $links
     * @param FeedEntry[] $entries
     */
    public function __construct(
        public string $title,
        public string $id,
        public string $updated,
        public string $streamId,
        public bool $headOfStream,
        public string $selfUrl,
        public string $eTag,
        public array $links,
        public array $entries,
        public EntryEmbedMode $embedMode,
    ) {
    }

    public function getLink(LinkRelation $relation): ?Link
    {
        return array_find($this->links, fn ($link) => $link->relation === $relation);
    }

    public function getLinkUrl(LinkRelation $relation): ?UriInterface
    {
        $link = $this->getLink($relation);

        return $link?->uri;
    }

    public function hasLink(LinkRelation $relation): bool
    {
        return null !== $this->getLink($relation);
    }

    /**
     * @return FeedEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getEntryEmbedMode(): EntryEmbedMode
    {
        return $this->embedMode;
    }
}
