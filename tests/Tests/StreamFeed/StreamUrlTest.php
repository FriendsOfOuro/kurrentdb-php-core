<?php

declare(strict_types=1);

namespace KurrentDB\Tests\StreamFeed;

use KurrentDB\StreamFeed\StreamUrl;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StreamUrlTest extends TestCase
{
    #[Test]
    public function it_should_be_built_from_base_url_and_name(): void
    {
        $url = StreamUrl::fromBaseUrlAndName(
            'http://foobar.com/',
            'gregorio'
        );

        $this->assertEquals(
            'http://foobar.com/streams/gregorio',
            $url->__toString()
        );
    }
}
