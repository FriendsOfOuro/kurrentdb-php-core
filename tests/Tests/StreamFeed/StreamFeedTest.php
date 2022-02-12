<?php
namespace EventStore\Tests\StreamFeed;

use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamFeedTest.
 */
class StreamFeedTest extends TestCase
{
    /**
     * @test
     * @dataProvider modeProvider
     *
     * @param EntryEmbedMode $mode
     */
    public function event_embed_mode_is_returned_properly(EntryEmbedMode $mode = null, EntryEmbedMode $expected)
    {
        $feed = new StreamFeed([], $mode);

        $this->assertEquals($expected, $feed->getEntryEmbedMode());
    }

    /**
     * @return array
     */
    public static function modeProvider()
    {
        return [
            [null, EntryEmbedMode::NONE()],
            [$eem = EntryEmbedMode::NONE(), $eem],
            [$eem = EntryEmbedMode::RICH(), $eem],
            [$eem = EntryEmbedMode::BODY(), $eem],
        ];
    }

    /**
     * @dataProvider relationProvider
     * @test
     */
    public function get_link_url_returns_proper_url(LinkRelation $relation)
    {
        $uri = 'http://sample.uri:12345/stream';

        $feed = new StreamFeed([
            'links' => [
                [
                    'relation' => (string) $relation,
                    'uri' => $uri,
                ],
            ],
        ]);

        $this->assertEquals($uri, $feed->getLinkUrl($relation));
    }

    /**
     * @test
     */
    public function has_link_returns_true_on_matching_url()
    {
        $feed = new StreamFeed([
            'links' => [
                [
                    'relation' => 'last',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $this->assertTrue($feed->hasLink(LinkRelation::LAST()));
    }

    /**
     * @test
     */
    public function has_link_returns_false_on_missing_url()
    {
        $feed = new StreamFeed([
            'links' => [
                [
                    'relation' => 'first',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $this->assertFalse($feed->hasLink(LinkRelation::LAST()));
    }

    /**
     * @test
     */
    public function get_link_url_returns_null_on_missing_url()
    {
        $feed = new StreamFeed([
            'links' => [
                [
                    'relation' => 'first',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $this->assertNull($feed->getLinkUrl(LinkRelation::LAST()));
    }

    public static function relationProvider()
    {
        return [
            [LinkRelation::FIRST()],
            [LinkRelation::LAST()],
        ];
    }
}
