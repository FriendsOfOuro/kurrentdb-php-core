<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;

/**
 * Abstract base class for all entry types (feed entries and single event responses).
 */
abstract readonly class Entry
{
    /**
     * @param Link[] $links
     */
    public function __construct(
        public string $title,
        public string $id,
        public string $updated,
        public string $summary,
        public ?int $retryCount,
        public array $links,
    ) {
    }

    public function getLink(LinkRelation $relation): ?Link
    {
        foreach ($this->links as $link) {
            if ($link->relation === $relation) {
                return $link;
            }
        }

        return null;
    }

    public function getAlternateUrl(): ?UriInterface
    {
        $link = $this->getLink(LinkRelation::ALTERNATE);

        return $link?->uri;
    }

    public function getEventUrl(): ?UriInterface
    {
        return $this->getAlternateUrl();
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
