<?php

declare(strict_types=1);

/**
 * Add or replace a query parameter in a URI.
 */
function with_query_value(string $uri, string $key, string $value): string
{
    $parts = parse_url($uri);

    if (isset($parts['query'])) {
        parse_str($parts['query'], $queryParams);
    } else {
        $queryParams = [];
    }

    $queryParams[$key] = $value;
    $parts['query'] = http_build_query($queryParams);

    return unparse_url($parts);
}

/**
 * Opposite of parse_url()
 * Taken from https://stackoverflow.com/questions/4354904/php-parse-url-reverse-parsed-url.
 */
function unparse_url(array $parsed): string
{
    $pass = $parsed['pass'] ?? null;
    $user = $parsed['user'] ?? null;
    $userinfo = null !== $pass ? "$user:$pass" : $user;
    $port = $parsed['port'] ?? 0;
    $scheme = $parsed['scheme'] ?? '';
    $query = $parsed['query'] ?? null;
    $fragment = $parsed['fragment'] ?? null;
    $authority = (
        (null !== $userinfo ? "$userinfo@" : '').
        ($parsed['host'] ?? '').
        ($port ? ":$port" : '')
    );

    return
        (\strlen($scheme) > 0 ? "$scheme:" : '').
        (\strlen($authority) > 0 ? "//$authority" : '').
        ($parsed['path'] ?? '').
        (null !== $query ? "?$query" : '').
        (null !== $fragment ? "#$fragment" : '');
}
