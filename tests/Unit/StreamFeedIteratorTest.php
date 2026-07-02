<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\EntryWithEvent;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\StreamReaderInterface;
use KurrentDB\Tests\SerializerFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class StreamFeedIteratorTest extends TestCase
{
    private StreamReaderInterface&MockObject $streamReader;

    private SerializerInterface $serializer;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->streamReader = $this->createMock(StreamReaderInterface::class);
        $this->serializer = SerializerFactory::create();
    }

    /**
     * @throws MockException
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function iterator_respects_page_limit(): void
    {
        $streamName = 'test-stream';
        $pageLimit = 2;

        $streamFeed1 = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => 'event1', 'eventType' => 'event1', 'eventNumber' => 0],
            ['title' => 'event2', 'eventType' => 'event2', 'eventNumber' => 1],
        ], true);
        $streamFeed2 = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => 'event3', 'eventType' => 'event3', 'eventNumber' => 2],
            ['title' => 'event4', 'eventType' => 'event4', 'eventNumber' => 3],
        ], true);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed1)
        ;

        $this->streamReader
            ->expects($this->exactly(1))
            ->method('navigateStreamFeed')
            ->willReturn($streamFeed2)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName, $pageLimit);
        $entriesWithEvents = iterator_to_array($iterator);

        $this->assertCount(4, $entriesWithEvents);
        $this->assertContainsOnlyInstancesOf(EntryWithEvent::class, $entriesWithEvents);
    }

    /**
     * @throws MockException
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function iterator_stops_when_no_more_navigation_links(): void
    {
        $streamName = 'test-stream';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => 'event1', 'eventType' => 'event1', 'eventNumber' => 0],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed)
        ;

        $this->streamReader
            ->expects($this->once())
            ->method('navigateStreamFeed')
            ->willReturn(null)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $entriesWithEvents = iterator_to_array($iterator);

        $this->assertCount(1, $entriesWithEvents);
        $this->assertContainsOnlyInstancesOf(EntryWithEvent::class, $entriesWithEvents);
    }

    /**
     * @throws BadRequestException
     * @throws MockException
     * @throws SerializerExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function next_url_returns_correct_navigation_url(): void
    {
        $streamName = 'test-stream';
        $expectedUrl = 'http://127.0.0.1:2113/streams/test-stream/next';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => 'event1', 'eventType' => 'event1', 'eventNumber' => 0],
        ], true);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);
        $iterator->rewind();

        $this->assertEquals($expectedUrl, $iterator->nextUrl());
    }

    /**
     * @throws MockException
     */
    #[Test]
    public function next_url_returns_null_when_no_feed(): void
    {
        $streamName = 'test-stream';

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $this->assertNull($iterator->nextUrl());
    }

    /**
     * @throws MockException
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function iterator_handles_empty_streams(): void
    {
        $streamName = 'empty-stream';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
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
     * @throws BadRequestException
     * @throws MockException
     * @throws SerializerExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function iterator_key_returns_entry_title(): void
    {
        $streamName = 'test-stream';
        $expectedTitle = 'event-title-123';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => $expectedTitle, 'eventType' => 'TestEvent', 'eventNumber' => 0],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed)
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
     * @throws BadRequestException
     * @throws MockException
     * @throws SerializerExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function iterator_rewind_is_idempotent(): void
    {
        $streamName = 'test-stream';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => 'event1', 'eventType' => 'event1', 'eventNumber' => 0],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);

        $iterator->rewind();
        $iterator->rewind();
        $iterator->rewind();

        $this->assertTrue($iterator->valid());
    }

    /**
     * @throws MockException
     * @throws SerializerExceptionInterface
     */
    #[Test]
    public function iterator_handles_system_events_with_null_data(): void
    {
        $streamName = '$all';

        $streamFeed = $this->createStreamFeedWithEmbeddedEvents([
            ['title' => '1@test-stream', 'eventType' => 'UserCreated', 'eventNumber' => 1, 'data' => '{"name":"test"}'],
            ['title' => '0@$projections-$all', 'eventType' => '$ProjectionCreated', 'eventNumber' => 0, 'data' => null],
        ], false);

        $this->streamReader
            ->expects($this->once())
            ->method('openStreamFeed')
            ->with($streamName, EntryEmbedMode::BODY)
            ->willReturn($streamFeed)
        ;

        $iterator = StreamFeedIterator::forward($this->streamReader, $streamName);
        $entriesWithEvents = array_values(iterator_to_array($iterator));

        $this->assertCount(2, $entriesWithEvents);

        // Forward iterator reverses feed order (newest-first → oldest-first), so $ProjectionCreated is at index 0
        $systemEvent = $entriesWithEvents[0];
        $this->assertEquals('$ProjectionCreated', $systemEvent->getEvent()->getType());
        $this->assertEquals([], $systemEvent->getEvent()->getData());
    }

    /**
     * @param array<array<string, mixed>> $entries
     *
     * @throws SerializerExceptionInterface
     */
    private function createStreamFeedWithEmbeddedEvents(array $entries, bool $hasNavigation): StreamFeed
    {
        $entriesData = array_map(fn (array $entry): array => [
            'title' => $entry['title'],
            'eventType' => $entry['eventType'],
            'eventNumber' => $entry['eventNumber'],
            'data' => array_key_exists('data', $entry) ? $entry['data'] : '{"test":"data"}',
            'eventId' => $entry['eventId'] ?? 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
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

        return $this->serializer->deserialize(
            json_encode($json),
            StreamFeed::class,
            'json',
            ['embedMode' => EntryEmbedMode::BODY]
        );
    }
}
