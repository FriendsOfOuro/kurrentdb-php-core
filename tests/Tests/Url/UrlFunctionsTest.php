<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Url;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function KurrentDB\Url\unparse_url;

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
}
