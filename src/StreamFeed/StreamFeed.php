<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Http\Auth\Credentials;

/**
 * Class StreamFeed.
 */
final readonly class StreamFeed
{
    use HasLinks;

    private EntryEmbedMode $entryEmbedMode;

    /**
     * @param Link[] $links
     */
    public function __construct(
        private array $links,
        private array $json,
        EntryEmbedMode $embedMode,
        private Credentials $credentials,
        private EntryFactory $entryFactory,
    ) {
        $this->entryEmbedMode = $embedMode;
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return array_map(
            fn (array $jsonEntry): Entry => $this->entryFactory->create($jsonEntry, $this->credentials),
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
        return $this->links;
    }

    protected function getCredentials(): Credentials
    {
        return $this->credentials;
    }
}
