<?php

declare(strict_types=1);

namespace KurrentDB\Tests\StreamFeed;

use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamFeedTest.
 */
class StreamFeedTest extends TestCase
{
    private HttpFactory $uriFactory;

    protected function setUp(): void
    {
        $this->uriFactory = new HttpFactory();
    }

    #[Test]
    #[DataProvider('modeProvider')]
    public function event_embed_mode_is_returned_properly(?EntryEmbedMode $mode, EntryEmbedMode $expected): void
    {
        $feed = new StreamFeed($this->uriFactory, [], $mode);

        $this->assertEquals($expected, $feed->getEntryEmbedMode());
    }

    /**
     * @return array{?EntryEmbedMode, EntryEmbedMode}[]
     */
    public static function modeProvider(): array
    {
        return [
            [null, EntryEmbedMode::NONE],
            [$eem = EntryEmbedMode::NONE, $eem],
            [$eem = EntryEmbedMode::RICH, $eem],
            [$eem = EntryEmbedMode::BODY, $eem],
        ];
    }

    #[Test]
    #[DataProvider('relationProvider')]
    public function get_link_url_returns_proper_url(LinkRelation $relation): void
    {
        $uri = 'http://sample.uri:12345/stream';

        $feed = new StreamFeed(
            $this->uriFactory,
            [
                'links' => [
                    [
                        'relation' => $relation->value,
                        'uri' => $uri,
                    ],
                ],
            ]
        );

        $this->assertSame($uri, (string) $feed->getLinkUrl($relation));
    }

    #[Test]
    public function has_link_returns_true_on_matching_url(): void
    {
        $feed = new StreamFeed(
            $this->uriFactory,
            [
                'links' => [
                    [
                        'relation' => 'last',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ]
        );

        $this->assertTrue($feed->hasLink(LinkRelation::LAST));
    }

    #[Test]
    public function has_link_returns_false_on_missing_url(): void
    {
        $feed = new StreamFeed(
            $this->uriFactory,
            [
                'links' => [
                    [
                        'relation' => 'first',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ]
        );

        $this->assertFalse($feed->hasLink(LinkRelation::LAST));
    }

    #[Test]
    public function get_link_url_returns_null_on_missing_url(): void
    {
        $feed = new StreamFeed(
            $this->uriFactory,
            [
                'links' => [
                    [
                        'relation' => 'first',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ]
        );

        $this->assertNull($feed->getLinkUrl(LinkRelation::LAST));
    }

    public static function relationProvider(): array
    {
        return [
            [LinkRelation::FIRST],
            [LinkRelation::LAST],
        ];
    }
}
