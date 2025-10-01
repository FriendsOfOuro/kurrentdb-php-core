<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\EntryFactory;
use KurrentDB\StreamFeed\EntryWithEvent;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedFactory;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\StreamReaderInterface;
use KurrentDB\ValueObjects\Identity\UUID;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreamFeedIteratorTest extends TestCase
{
    private StreamReaderInterface&MockObject $streamReader;

    private HttpFactory $uriFactory;

    private StreamFeedFactory $streamFeedFactory;

    protected function setUp(): void
    {
        $this->streamReader = $this->createMock(StreamReaderInterface::class);
        $this->uriFactory = new HttpFactory();

        $entryFactory = new EntryFactory($this->uriFactory);
        $this->streamFeedFactory = new StreamFeedFactory($this->uriFactory, $entryFactory);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function iterator_respects_page_limit(): void
    {
        $streamName = 'test-stream';
        $pageLimit = 2;

        $streamFeed1 = $this->createStreamFeed([
            ['title' => 'event1'],
            ['title' => 'event2'],
        ], true);
        $streamFeed2 = $this->createStreamFeed([
            ['title' => 'event3'],
            ['title' => 'event4'],
        ], true);
        $this->createStreamFeed([
            ['title' => 'event5'],
            ['title' => 'event6'],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed1)
        ;

        $this->streamReader
            ->expects($this->exactly(1))
            ->method('navigateStreamFeed')
            ->willReturn($streamFeed2)
        ;

        $this->streamReader
            ->expects($this->exactly(2))
            ->method('readEventBatch')
            ->willReturn([
                $this->createEvent('event1', 0),
                $this->createEvent('event2', 1),
            ])
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName, $pageLimit);
        $entriesWithEvents = iterator_to_array($iterator);

        $this->assertCount(4, $entriesWithEvents);
        $this->assertContainsOnlyInstancesOf(EntryWithEvent::class, $entriesWithEvents);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function iterator_stops_when_no_more_navigation_links(): void
    {
        $streamName = 'test-stream';

        $streamFeed = $this->createStreamFeed([
            ['title' => 'event1'],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('navigateStreamFeed')
            ->willReturn(null)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('readEventBatch')
            ->willReturn([$this->createEvent('event1', 0)])
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $entriesWithEvents = iterator_to_array($iterator);

        $this->assertCount(1, $entriesWithEvents);
        $this->assertContainsOnlyInstancesOf(EntryWithEvent::class, $entriesWithEvents);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function next_url_returns_correct_navigation_url(): void
    {
        $streamName = 'test-stream';
        $expectedUrl = 'http://127.0.0.1:2113/streams/test-stream/next';

        $streamFeed = $this->createStreamFeed([
            ['title' => 'event1'],
        ], true);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('readEventBatch')
            ->willReturn([$this->createEvent('event1', 0)])
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);
        $iterator->rewind();

        $this->assertEquals($expectedUrl, $iterator->nextUrl());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function next_url_returns_null_when_no_feed(): void
    {
        $streamName = 'test-stream';

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $this->assertNull($iterator->nextUrl());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function iterator_handles_empty_streams(): void
    {
        $streamName = 'empty-stream';

        $streamFeed = $this->createStreamFeed([], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $eventCount = 0;
        foreach ($iterator as $entryWithEvent) {
            ++$eventCount;
        }

        $this->assertEquals(0, $eventCount);
        $this->assertFalse($iterator->valid());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function iterator_key_returns_entry_title(): void
    {
        $streamName = 'test-stream';
        $expectedTitle = 'event-title-123';

        $streamFeed = $this->createStreamFeed([['title' => $expectedTitle]], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('readEventBatch')
            ->willReturn([$this->createEvent('event1', 0)])
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);
        $iterator->rewind();

        if ($iterator->valid()) {
            $this->assertEquals($expectedTitle, $iterator->key());
        } else {
            $this->fail('Iterator should be valid after rewind with data');
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function iterator_rewind_is_idempotent(): void
    {
        $streamName = 'test-stream';

        $streamFeed = $this->createStreamFeed([
            ['title' => 'event1'],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName)
            ->willReturn($streamFeed)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('readEventBatch')
            ->willReturn([$this->createEvent('event1', 0)])
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $iterator->rewind();
        $iterator->rewind();
        $iterator->rewind();

        $this->assertTrue($iterator->valid());
    }

    /** @param array<array<string, mixed>> $entries */
    private function createStreamFeed(array $entries, bool $hasNavigation): StreamFeed
    {
        $entriesData = array_map(fn (array $entry): array => [
            'title' => $entry['title'],
            'links' => [
                [
                    'uri' => "http://127.0.0.1:2113/streams/test-stream/{$entry['title']}",
                    'relation' => 'alternate',
                ],
            ],
        ], $entries);

        $links = [];
        if ($hasNavigation) {
            $links[] = [
                'uri' => 'http://127.0.0.1:2113/streams/test-stream/next',
                'relation' => 'previous',
            ];
        }

        $json = [
            'entries' => $entriesData,
            'links' => $links,
        ];

        return $this->streamFeedFactory->create(
            $json,
            EntryEmbedMode::NONE
        );
    }

    private function createEvent(string $type, int $version): Event
    {
        return new Event(
            $type,
            $version,
            ['test' => 'data'],
            null,
            new UUID()
        );
    }
}
