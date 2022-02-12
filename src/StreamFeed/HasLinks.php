<?php
namespace EventStore\StreamFeed;

/**
 * Class HasLinks.
 */
trait HasLinks
{
    /**
     * @return array
     */
    abstract protected function getLinks();

    /**
     * @return string|null
     */
    public function getLinkUrl(LinkRelation $relation,
        array $credentials = ['user' => null, 'pass' => null]
    ) {
        $links = $this->getLinks();

        $uri = null;
        foreach ($links as $link) {
            if ($link['relation'] == $relation->toNative()) {
                $uri = $link['uri'];
                break;
            }
        }

        if (!$uri) {
            return $uri;
        }

        $parts = parse_url($uri);
        $parts['user'] = $credentials['user'];
        $parts['pass'] = $credentials['pass'];
        $uri = \unparse_url($parts);

        return $uri;
    }

    /**
     * @return bool
     */
    public function hasLink(LinkRelation $relation)
    {
        return null !== $this->getLinkUrl($relation, $this->credentials);
    }
}
