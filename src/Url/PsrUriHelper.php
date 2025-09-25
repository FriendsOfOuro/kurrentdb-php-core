<?php

declare(strict_types=1);

namespace KurrentDB\Url;

use Psr\Http\Message\UriInterface;

/**
 * Helper class for PSR-7 URI manipulation.
 */
final class PsrUriHelper
{
    /**
     * Add or replace a query parameter in a URI.
     */
    public static function withQueryValue(UriInterface $uri, string $key, string $value): UriInterface
    {
        parse_str($uri->getQuery(), $queryParams);
        $queryParams[$key] = $value;

        return $uri->withQuery(http_build_query($queryParams));
    }
}
