<?php
namespace EventStore\StreamFeed;

/**
 * Class StreamFeed.
 */
final class StreamFeed
{
    use HasLinks;

    /**
     * @var array
     */
    private $json;

    /**
     * @var EntryEmbedMode
     */
    private $entryEmbedMode;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @param EntryEmbedMode $embedMode
     */
    public function __construct(
        array $jsonFeed,
        EntryEmbedMode $embedMode = null,
        array $credentials = ['user' => null, 'pass' => null]
    ) {
        if (null === $embedMode) {
            $embedMode = EntryEmbedMode::NONE();
        }

        $this->entryEmbedMode = $embedMode;
        $this->json = $jsonFeed;
        $this->credentials = $credentials;
    }

    /**
     * @return Entry[]
     */
    public function getEntries()
    {
        return array_map(
            function (array $jsonEntry) {
                return new Entry($jsonEntry, $this->credentials);
            },
            $this->json['entries']
        );
    }

    /**
     * @return EntryEmbedMode
     */
    public function getEntryEmbedMode()
    {
        return $this->entryEmbedMode;
    }

    /**
     * @return array
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * @return array
     */
    protected function getLinks()
    {
        return $this->json['links'];
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return $this->credentials;
    }
}
