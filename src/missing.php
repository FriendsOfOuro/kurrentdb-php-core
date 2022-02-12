<?php

/**
* Opposit of parse_url()
* Taken from https://stackoverflow.com/questions/4354904/php-parse-url-reverse-parsed-url
*/
function unparse_url(array $parsed): string {
    $pass      = $parsed['pass'] ?? null;
    $user      = $parsed['user'] ?? null;
    $userinfo  = $pass !== null ? "$user:$pass" : $user;
    $port      = $parsed['port'] ?? 0;
    $scheme    = $parsed['scheme'] ?? "";
    $query     = $parsed['query'] ?? "";
    $fragment  = $parsed['fragment'] ?? "";
    $authority = (
        ($userinfo !== null ? "$userinfo@" : "") .
        ($parsed['host'] ?? "") .
        ($port ? ":$port" : "")
    );
    return (
        (\strlen($scheme) > 0 ? "$scheme:" : "") .
        (\strlen($authority) > 0 ? "//$authority" : "") .
        ($parsed['path'] ?? "") .
        (\strlen($query) > 0 ? "?$query" : "") .
        (\strlen($fragment) > 0 ? "#$fragment" : "")
    );
}
