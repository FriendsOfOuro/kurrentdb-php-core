<?php
namespace EventStore\StreamFeed;

use EventStore\StreamFeed\LinkRelation;

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
     * @param LinkRelation $relation
     *
     * @return null|string
     */
    public function getLinkUrl(LinkRelation
        $relation,
        array $credentials=['user'=> null, 'pass' => null]
    ){
        $links = $this->getLinks();

        $uri = null;
        foreach ($links as $link) {
            if ($link['relation'] == $relation->toNative()) {
                $uri = $link['uri'];
                break;
            }
        }

        if (! $uri) {
            return $uri;
        }

        $parts = parse_url($uri);
        $parts['user'] = $credentials['user'];
        $parts['pass'] = $credentials['pass'];
        $uri = \unparse_url($parts);

        return $uri;
    }

    /**
     * @param LinkRelation $relation
     *
     * @return bool
     */
    public function hasLink(LinkRelation $relation)
    {
        return null !== $this->getLinkUrl($relation, $this->credentials);
    }
}
