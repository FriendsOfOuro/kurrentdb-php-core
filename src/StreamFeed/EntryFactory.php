<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Http\Auth\Credentials;
use Psr\Http\Message\UriFactoryInterface;

final readonly class EntryFactory
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
    ) {
    }

    public function create(array $json, Credentials $credentials): Entry
    {
        $links = $this->createLinks($json['links'] ?? []);

        return new Entry($links, $json, $credentials);
    }

    /**
     * @param array<array{relation: string, uri: string}> $linksData
     *
     * @return Link[]
     */
    private function createLinks(array $linksData): array
    {
        return array_map(
            fn (array $linkData): Link => new Link(
                LinkRelation::from($linkData['relation']),
                $this->uriFactory->createUri($linkData['uri']),
            ),
            $linksData
        );
    }
}
