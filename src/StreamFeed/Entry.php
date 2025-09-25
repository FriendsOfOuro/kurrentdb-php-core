<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Http\Auth\Credentials;
use Psr\Http\Message\UriInterface;

/**
 * Class Entry.
 */
final readonly class Entry
{
    use HasLinks;

    /**
     * @param Link[] $links
     */
    public function __construct(
        private array $links,
        private array $json,
        private Credentials $credentials,
    ) {
    }

    public function getEventUrl(): ?UriInterface
    {
        return $this->getLinkUrl(LinkRelation::ALTERNATE, $this->credentials);
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
