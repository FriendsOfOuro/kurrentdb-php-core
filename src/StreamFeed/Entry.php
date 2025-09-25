<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Entry.
 */
final readonly class Entry
{
    use HasLinks;

    public function __construct(
        private UriFactoryInterface $uriFactory,
        private array $json,
        private array $credentials,
    ) {
    }

    public function getEventUrl(): ?UriInterface
    {
        return $this->getLinkUrl(LinkRelation::ALTERNATE, $this->credentials);
    }

    public function getTitle()
    {
        return $this->json['title'];
    }

    protected function getLinks(): array
    {
        return $this->json['links'];
    }
}
