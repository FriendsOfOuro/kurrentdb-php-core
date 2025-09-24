<?php

namespace KurrentDB\StreamFeed;

final readonly class StreamUrl implements \Stringable
{
    public static function fromBaseUrlAndName(string $baseUrl, string $name): self
    {
        $baseUrl = rtrim($baseUrl, '/');

        return new self("$baseUrl/streams/$name");
    }

    private function __construct(private string $url)
    {
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
