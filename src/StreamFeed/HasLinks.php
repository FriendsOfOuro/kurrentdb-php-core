<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Http\Auth\Credentials;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class HasLinks.
 */
trait HasLinks
{
    private readonly Credentials $credentials;

    private readonly UriFactoryInterface $uriFactory;

    abstract protected function getLinks(): array;

    public function getLinkUrl(
        LinkRelation $relation,
        ?Credentials $credentials = null,
    ): ?UriInterface {
        $links = $this->getLinks();

        $uri = null;
        foreach ($links as $link) {
            if ($link['relation'] === $relation->value) {
                $uri = $this->uriFactory->createUri($link['uri']);
                break;
            }
        }

        if (!$uri) {
            return $uri;
        }

        $creds = $credentials ?? $this->credentials;

        return $uri->withUserInfo($creds->user, $creds->pass);
    }

    public function hasLink(LinkRelation $relation): bool
    {
        return null !== $this->getLinkUrl($relation, $this->credentials);
    }
}
