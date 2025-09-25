<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriFactoryInterface;

/**
 * Class StreamFeed.
 */
final readonly class StreamFeed
{
    use HasLinks;

    private EntryEmbedMode $entryEmbedMode;

    public function __construct(
        private UriFactoryInterface $uriFactory,
        private array $json,
        ?EntryEmbedMode $embedMode = null,
        private array $credentials = ['user' => null, 'pass' => null],
    ) {
        $this->entryEmbedMode = $embedMode ?? EntryEmbedMode::NONE;
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return array_map(
            fn (array $jsonEntry): Entry => new Entry($this->uriFactory, $jsonEntry, $this->credentials),
            $this->json['entries']
        );
    }

    public function getEntryEmbedMode(): EntryEmbedMode
    {
        return $this->entryEmbedMode;
    }

    public function getJson(): array
    {
        return $this->json;
    }

    protected function getLinks(): array
    {
        return $this->json['links'];
    }

    protected function getCredentials(): array
    {
        return $this->credentials;
    }
}
