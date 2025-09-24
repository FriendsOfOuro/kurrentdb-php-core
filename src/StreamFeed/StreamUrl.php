<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

final readonly class StreamUrl implements \Stringable
{
    private function __construct(private string $url)
    {
    }

    public static function fromBaseUrlAndName(string $baseUrl, string $name): self
    {
        $baseUrl = rtrim($baseUrl, '/');

        return new self("$baseUrl/streams/$name");
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
