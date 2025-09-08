<?php

namespace EventStore\StreamFeed;

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
    ): ?string {
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

        $parts = parse_url((string) $uri);
        $parts['user'] = $credentials['user'];
        $parts['pass'] = $credentials['pass'];

        return \unparse_url($parts);
    }

    public function hasLink(LinkRelation $relation): bool
    {
        return null !== $this->getLinkUrl($relation, $this->credentials);
    }
}
