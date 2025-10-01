<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\StreamFeed;

use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\FeedEntryDenormalizer;
use KurrentDB\StreamFeed\LinkDenormalizer;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeedDenormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamFeedTest.
 */
class StreamFeedTest extends TestCase
{
    private StreamFeedDenormalizer $streamFeedDenormalizer;

    protected function setUp(): void
    {
        $uriFactory = new HttpFactory();
        $linkDenormalizer = new LinkDenormalizer($uriFactory);
        $feedEntryDenormalizer = new FeedEntryDenormalizer($linkDenormalizer);
        $this->streamFeedDenormalizer = new StreamFeedDenormalizer($linkDenormalizer, $feedEntryDenormalizer);
    }

    #[Test]
    #[DataProvider('modeProvider')]
    public function event_embed_mode_is_returned_properly(?EntryEmbedMode $mode, EntryEmbedMode $expected): void
    {
        $feed = $this->streamFeedDenormalizer->create([], $mode);

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

        $feed = $this->streamFeedDenormalizer->create(
            [
                'links' => [
                    [
                        'relation' => $relation->value,
                        'uri' => $uri,
                    ],
                ],
            ],
            EntryEmbedMode::NONE
        );

        $this->assertSame($uri, (string) $feed->getLinkUrl($relation));
    }

    #[Test]
    public function has_link_returns_true_on_matching_url(): void
    {
        $feed = $this->streamFeedDenormalizer->create(
            [
                'links' => [
                    [
                        'relation' => 'last',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ],
            EntryEmbedMode::NONE,
        );

        $this->assertTrue($feed->hasLink(LinkRelation::LAST));
    }

    #[Test]
    public function has_link_returns_false_on_missing_url(): void
    {
        $feed = $this->streamFeedDenormalizer->create(
            [
                'links' => [
                    [
                        'relation' => 'first',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ],
            EntryEmbedMode::NONE,
        );

        $this->assertFalse($feed->hasLink(LinkRelation::LAST));
    }

    #[Test]
    public function get_link_url_returns_null_on_missing_url(): void
    {
        $feed = $this->streamFeedDenormalizer->create(
            [
                'links' => [
                    [
                        'relation' => 'first',
                        'uri' => 'http://sample.uri:12345/stream',
                    ],
                ],
            ],
            EntryEmbedMode::NONE,
        );

        $this->assertNull($feed->getLinkUrl(LinkRelation::LAST));
    }

    /** @return array<array<LinkRelation>> */
    public static function relationProvider(): array
    {
        return [
            [LinkRelation::FIRST],
            [LinkRelation::LAST],
        ];
    }
}
