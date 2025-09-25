<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;
use function KurrentDB\Url\unparse_url;

/**
 * Class HasLinks.
 */
trait HasLinks
{
    private readonly array $credentials;

    abstract protected function getLinks(): array;

    public function getLinkUrl(
        LinkRelation $relation,
        array $credentials = ['user' => null, 'pass' => null],
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

        return $uri->withUserInfo((string) $credentials['user'], $credentials['pass']);
    }

    public function hasLink(LinkRelation $relation): bool
    {
        return null !== $this->getLinkUrl($relation, $this->credentials);
    }
}
