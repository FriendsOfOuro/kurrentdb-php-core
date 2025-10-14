<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\StreamFeed;

use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\Tests\SerializerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class StreamFeedTest.
 */
class StreamFeedTest extends TestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->serializer = SerializerFactory::create();
    }

    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function event_embed_mode_defaults_to_none(): void
    {
        $feed = $this->serializer->deserialize('[]', StreamFeed::class, 'json');

        $this->assertEquals(EntryEmbedMode::NONE, $feed->getEntryEmbedMode());
    }

    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    #[DataProvider('relationProvider')]
    public function get_link_url_returns_proper_url(LinkRelation $relation): void
    {
        $uri = 'http://sample.uri:12345/stream';

        $json = json_encode([
            'links' => [
                [
                    'relation' => $relation->value,
                    'uri' => $uri,
                ],
            ],
        ]);

        $feed = $this->serializer->deserialize($json, StreamFeed::class, 'json');

        $this->assertSame($uri, (string) $feed->getLinkUrl($relation));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function has_link_returns_true_on_matching_url(): void
    {
        $json = json_encode([
            'links' => [
                [
                    'relation' => 'last',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $feed = $this->serializer->deserialize($json, StreamFeed::class, 'json');

        $this->assertTrue($feed->hasLink(LinkRelation::LAST));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function has_link_returns_false_on_missing_url(): void
    {
        $json = json_encode([
            'links' => [
                [
                    'relation' => 'first',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $feed = $this->serializer->deserialize($json, StreamFeed::class, 'json');

        $this->assertFalse($feed->hasLink(LinkRelation::LAST));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function get_link_url_returns_null_on_missing_url(): void
    {
        $json = json_encode([
            'links' => [
                [
                    'relation' => 'first',
                    'uri' => 'http://sample.uri:12345/stream',
                ],
            ],
        ]);

        $feed = $this->serializer->deserialize($json, StreamFeed::class, 'json');

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
