<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Http\Auth\Credentials;
use Psr\Http\Message\UriFactoryInterface;

final readonly class StreamFeedFactory
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private EntryFactory $entryFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $json
     */
    public function create(
        array $json,
        ?EntryEmbedMode $embedMode = null,
        Credentials $credentials = new Credentials(''),
    ): StreamFeed {
        $links = $this->createLinks($json['links'] ?? []);

        return new StreamFeed(
            $links,
            $json,
            $embedMode ?? EntryEmbedMode::NONE,
            $credentials,
            $this->entryFactory,
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
