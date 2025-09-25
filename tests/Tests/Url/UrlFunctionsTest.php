<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Url;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function KurrentDB\Url\unparse_url;
use function KurrentDB\Url\with_query_value;

class UrlFunctionsTest extends TestCase
{
    /**
     * @see https://stackoverflow.com/a/31691249/2714285
     */
    #[Test]
    #[DataProvider('urlProvider')]
    public function unparsing_a_parsed_url_will_return_the_same_string(string $url): void
    {
        $this->assertSame($url, unparse_url(\parse_url($url) ?: []));
    }

    public static function urlProvider(): array
    {
        return [
            'empty string' => [''],
            'simple string' => ['foo'],
            'simple url' => ['http://www.google.com/'],
            'full url with auth and fragment' => ['http://u:p@foo:1/path/path?q#frag'],
            'url with empty query and fragment' => ['http://u:p@foo:1/path/path?#'],
            'ssh url' => ['ssh://root@host'],
            'minimal url components' => ['://:@:1/?#'],
            'http with empty auth' => ['http://:@foo:1/path/path?#'],
            'http with empty password' => ['http://@foo:1/path/path?#'],
        ];
    }

    #[Test]
    #[DataProvider('queryValueProvider')]
    public function with_query_value_adds_or_replaces_query_parameters(
        string $uri,
        string $key,
        string $value,
        string $expected,
    ): void {
        $this->assertSame($expected, with_query_value($uri, $key, $value));
    }

    public static function queryValueProvider(): array
    {
        return [
            'add param to url without query' => [
                'http://example.com/path',
                'foo',
                'bar',
                'http://example.com/path?foo=bar',
            ],
            'add param to url with existing query' => [
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
        ];
    }
}
