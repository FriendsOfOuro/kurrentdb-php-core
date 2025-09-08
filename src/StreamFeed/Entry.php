<?php

namespace EventStore\StreamFeed;

/**
 * Class Entry.
 */
final readonly class Entry
{
    use HasLinks;

    public function __construct(private array $json, private array $credentials)
    {
    }

    public function getEventUrl(): ?string
    {
        return $this->getLinkUrl(LinkRelation::ALTERNATE(), $this->credentials);
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
