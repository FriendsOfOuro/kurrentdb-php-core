<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\StreamFeed;

use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\StreamFeed\EntryDenormalizer;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\LinkDenormalizer;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeedDenormalizer;
use KurrentDB\StreamFeed\StreamFeedFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class StreamFeedTest.
 */
class StreamFeedTest extends TestCase
{
    private StreamFeedFactory $streamFeedFactory;

    protected function setUp(): void
    {
        $uriFactory = new HttpFactory();

        $linkDenormalizer = new LinkDenormalizer($uriFactory);
        $entryDenormalizer = new EntryDenormalizer($linkDenormalizer);
        $streamFeedDenormalizer = new StreamFeedDenormalizer($linkDenormalizer, $entryDenormalizer);

        $serializer = new Serializer(
            [
                $linkDenormalizer,
                $entryDenormalizer,
                $streamFeedDenormalizer,
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );

        $this->streamFeedFactory = new StreamFeedFactory($serializer);
    }

    #[Test]
    #[DataProvider('modeProvider')]
    public function event_embed_mode_is_returned_properly(EntryEmbedMode $mode): void
    {
        $feed = $this->streamFeedFactory->create([], $mode);

        $this->assertEquals($mode, $feed->getEntryEmbedMode());
    }

    #[Test]
    public function event_embed_mode_defaults_to_none(): void
    {
        $feed = $this->streamFeedFactory->create([]);

        $this->assertEquals(EntryEmbedMode::NONE, $feed->getEntryEmbedMode());
    }

    /**
     * @return array{EntryEmbedMode}[]
     */
    public static function modeProvider(): array
    {
        return [
            [EntryEmbedMode::NONE],
            [EntryEmbedMode::RICH],
            [EntryEmbedMode::BODY],
        ];
    }

    #[Test]
    #[DataProvider('relationProvider')]
    public function get_link_url_returns_proper_url(LinkRelation $relation): void
    {
        $uri = 'http://sample.uri:12345/stream';

        $feed = $this->streamFeedFactory->create(
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
        $feed = $this->streamFeedFactory->create(
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
        $feed = $this->streamFeedFactory->create(
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
        $feed = $this->streamFeedFactory->create(
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
