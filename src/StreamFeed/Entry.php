<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;

/**
 * Class Entry.
 */
final readonly class Entry
{
    use HasLinks;

    /**
     * @param Link[]               $links
     * @param array<string, mixed> $json
     */
    public function __construct(
        private array $links,
        private array $json,
    ) {
    }

    public function getEventUrl(): ?UriInterface
    {
        return $this->getLinkUrl(LinkRelation::ALTERNATE);
    }

    public function getTitle(): string
    {
        return $this->json['title'];
    }

    protected function getLinks(): array
    {
        return $this->links;
    }
}
