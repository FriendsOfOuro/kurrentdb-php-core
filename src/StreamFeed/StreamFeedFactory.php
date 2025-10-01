<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriFactoryInterface;

final readonly class StreamFeedFactory implements StreamFeedFactoryInterface
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private EntryFactoryInterface $entryFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $json
     */
    public function create(
        array $json,
        ?EntryEmbedMode $embedMode = null,
    ): StreamFeed {
        $links = $this->createLinks($json['links'] ?? []);

        $entries = [];
        if (isset($json['entries']) && is_array($json['entries'])) {
            foreach ($json['entries'] as $entryData) {
                $entries[] = $this->entryFactory->create($entryData);
            }
        }

        return new StreamFeed(
            $links,
            $entries,
            $json,
            $embedMode ?? EntryEmbedMode::NONE,
        );
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
