<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriFactoryInterface;

final readonly class EntryFactory implements EntryFactoryInterface
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $json
     */
    public function create(array $json): Entry
    {
        $links = $this->createLinks($json['links'] ?? []);

        return new Entry($links, $json);
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
