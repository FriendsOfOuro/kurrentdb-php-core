<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Url;

use GuzzleHttp\Psr7\Uri;
use KurrentDB\Url\PsrUriHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PsrUriHelperTest extends TestCase
{
    #[Test]
    #[DataProvider('queryValueProvider')]
    public function with_query_value_adds_or_replaces_query_parameters(
        string $initialUri,
        string $key,
        string $value,
        string $expectedUri,
    ): void {
        $uri = new Uri($initialUri);
        $result = PsrUriHelper::withQueryValue($uri, $key, $value);

        $this->assertSame($expectedUri, (string) $result);
    }

    public static function queryValueProvider(): array
    {
        return [
            'add param to uri without query' => [
                'http://example.com/path',
                'foo',
                'bar',
                'http://example.com/path?foo=bar',
            ],
            'add param to uri with existing query' => [
                'http://example.com/path?existing=value',
                'foo',
                'bar',
                'http://example.com/path?existing=value&foo=bar',
            ],
            'replace existing param' => [
                'http://example.com/path?foo=old&other=value',
                'foo',
                'new',
                'http://example.com/path?foo=new&other=value',
            ],
            'handle special characters in value' => [
                'http://example.com/path',
                'search',
                'hello world',
                'http://example.com/path?search=hello+world',
            ],
            'preserve fragment' => [
                'http://example.com/path#section',
                'foo',
                'bar',
                'http://example.com/path?foo=bar#section',
            ],
            'preserve auth and port' => [
                'http://user:pass@example.com:8080/path',
                'foo',
                'bar',
                'http://user:pass@example.com:8080/path?foo=bar',
            ],
            'handle empty value' => [
                'http://example.com/path',
                'foo',
                '',
                'http://example.com/path?foo=',
            ],
            'url encode special chars in key and value' => [
                'http://example.com/path',
                'key&special',
                'value=special',
                'http://example.com/path?key%26special=value%3Dspecial',
            ],
            'preserve scheme' => [
                'https://example.com/path',
                'secure',
                'yes',
                'https://example.com/path?secure=yes',
            ],
        ];
    }

    #[Test]
    public function with_query_value_returns_new_instance(): void
    {
        $original = new Uri('http://example.com');
        $modified = PsrUriHelper::withQueryValue($original, 'foo', 'bar');

        $this->assertNotSame($original, $modified);
        $this->assertSame('http://example.com', (string) $original);
        $this->assertSame('http://example.com?foo=bar', (string) $modified);
    }
}
