<?php

/**
 * Opposit of parse_url()
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
        (null !== $userinfo ? "$userinfo@" : '') .
        ($parsed['host'] ?? '') .
        ($port ? ":$port" : '')
    );

    return
        (\strlen($scheme) > 0 ? "$scheme:" : '') .
        (\strlen($authority) > 0 ? "//$authority" : '') .
        ($parsed['path'] ?? '') .
        (null !== $query ? "?$query" : '') .
        (null !== $fragment ? "#$fragment" : '')
    ;
}
