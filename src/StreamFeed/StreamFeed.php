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

    /**
     * @param Link[]               $links
     * @param array<string, mixed> $json
     */
    public function __construct(private array $links, private array $json, private EntryEmbedMode $entryEmbedMode, private Credentials $credentials, private EntryFactory $entryFactory)
    {
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

    /**
     * @return array<string, mixed>
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @return Link[]
     */
    protected function getLinks(): array
    {
        return $this->links;
    }

    protected function getCredentials(): Credentials
    {
        return $this->credentials;
    }
}
