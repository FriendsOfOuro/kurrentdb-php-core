<?php

namespace EventStore\StreamFeed;

/**
 * Class StreamFeed.
 */
final readonly class StreamFeed
{
    use HasLinks;

    private EntryEmbedMode $entryEmbedMode;

    public function __construct(
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
            fn (array $jsonEntry): Entry => new Entry($jsonEntry, $this->credentials),
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
