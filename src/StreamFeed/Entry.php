<?php
namespace EventStore\StreamFeed;

/**
 * Class Entry.
 */
final class Entry
{
    use HasLinks;

    /**
     * @var array
     */
    private $json;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @param array $json
     */
    public function __construct(array $json, array $credentials)
    {
        $this->credentials = $credentials;
        $this->json = $json;
    }

    /**
     * @return null|string
     */
    public function getEventUrl()
    {
        $alternate = $this->getLinkUrl(LinkRelation::ALTERNATE(), $this->credentials);

        return $alternate;
    }

    public function getTitle()
    {
        return $this->json['title'];
    }

    /**
     * @return array
     */
    protected function getLinks()
    {
        return $this->json['links'];
    }
}
