<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;

/**
 * Class HasLinks.
 */
trait HasLinks
{
    /**
     * @return Link[]
     */
    abstract protected function getLinks(): array;

    public function getLinkUrl(LinkRelation $relation): ?UriInterface
    {
        $links = $this->getLinks();

        foreach ($links as $link) {
            if ($link->isRelation($relation)) {
                return $link->uri;
            }
        }

        return null;
    }

    public function hasLink(LinkRelation $relation): bool
    {
        return null !== $this->getLinkUrl($relation);
    }
}
