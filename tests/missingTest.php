<?php

use PHPUnit\Framework\TestCase;

class missingTest extends TestCase
{
    /**
     * @test
     *
     * @see https://stackoverflow.com/a/31691249/2714285
     */
    public function urls_are_same_before_parse_url_and_after_unparse()
    {
        foreach ([
            '',
            'foo',
            'http://www.google.com/',
            'http://u:p@foo:1/path/path?q#frag',
            'http://u:p@foo:1/path/path?#',
            'ssh://root@host',
            '://:@:1/?#',
            'http://:@foo:1/path/path?#',
            'http://@foo:1/path/path?#',
        ] as $url) {
            $parsed1 = parse_url($url);
            $parsed2 = parse_url(unparse_url($parsed1));

            $this->assertEquals($parsed1, $parsed2);
        }
    }
}
