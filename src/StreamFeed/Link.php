<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriInterface;

final readonly class Link
{
    public function __construct(
        public LinkRelation $relation,
        public UriInterface $uri,
    ) {
    }

    public function isRelation(LinkRelation $relation): bool
    {
        return $this->relation === $relation;
    }
}
