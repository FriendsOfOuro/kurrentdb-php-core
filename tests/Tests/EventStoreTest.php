<?php

declare(strict_types=1);

namespace KurrentDB\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use KurrentDB\EventStore;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamDeletedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\GuzzleHttpClient;
use KurrentDB\StreamDeletion;
use KurrentDB\StreamFeed\Entry;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;

class EventStoreTest extends TestCase
{
    #[Test]
    public function client_successfully_connects_to_event_store(): void
    {
        $this->assertMatchesRegularExpression('/^[2-3]\d{2}$/', (string) $this->es->getLastResponse()->getStatusCode());
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function event_is_written_to_stream(): void
    {
        $this->prepareTestStream();

        $this->assertEquals('201', $this->es->getLastResponse()->getStatusCode());
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function version_is_provided_after_writing_to_stream(): void
    {
        $streamName = $this->prepareTestStream();
        $event = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar']);
        $result = $this->es->writeToStream($streamName, $event);

        $this->assertSame(1, $result->version);
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function wrong_expected_version_should_throw_exception(): void
    {
        $streamName = $this->prepareTestStream();
        $event = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar']);

        $this->expectException(WrongExpectedVersionException::class);
        $this->es->writeToStream($streamName, $event, 3);
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_is_soft_deleted(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::SOFT);

        $this->assertEquals('204', $this->es->getLastResponse()->getStatusCode());

        // we try to write to a soft deleted stream...
        $this->es->writeToStream($streamName, WritableEvent::newInstance('Foo_Event', ['bar']));

        // ..and we should expect a "201 Created" response
        $this->assertEquals('201', $this->es->getLastResponse()->getStatusCode());
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_is_hard_deleted(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->assertEquals('204', $this->es->getLastResponse()->getStatusCode());

        // we try to write to a hard deleted stream...
        $this->expectException(StreamGoneException::class);
        $this->es->writeToStream($streamName, WritableEvent::newInstance('Foo_Event', ['bar']));
    }

    /**
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_feed_is_successfully_opened(): void
    {
        $streamName = $this->prepareTestStream();
        $streamFeed = $this->es->openStreamFeed($streamName);

        $json = $streamFeed->getJson();

        $this->assertEquals($streamName, $json['streamId']);
    }

    #[Test]
    public function unreacheable_event_store_throws_exception(): void
    {
        $httpClient = new GuzzleHttpClient();
        $this->expectException(ConnectionFailedException::class);
        new EventStore('http://127.0.0.1:12345/', $httpClient);
    }

    /**
     * @throws StreamNotFoundException
     * @throws StreamDeletedException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function event_data_is_embedded_with_body_mode(): void
    {
        $streamName = $this->prepareTestStream();
        $streamFeed = $this->es->openStreamFeed($streamName, EntryEmbedMode::BODY);

        $json = $streamFeed->getJson();

        $this->assertEquals(['foo_data_key' => 'bar'], json_decode((string) $json['entries'][0]['data'], true));
    }

    /**
     * @throws StreamDeletedException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function event_stream_feed_head_returns_next_link(): void
    {
        $streamName = $this->prepareTestStream(40);

        $head = $this->es->openStreamFeed($streamName);
        $next = $this->es->navigateStreamFeed($head, LinkRelation::NEXT);

        $this->assertInstanceOf(StreamFeed::class, $next);
        $this->assertCount(20, $next->getJson()['entries']);
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function event_stream_feed_returns_entries(): void
    {
        $streamName = $this->prepareTestStream(40);
        $feed = $this->es->openStreamFeed($streamName);
        $entries = $feed->getEntries();

        $this->assertCount(20, $entries);
        $this->assertContainsOnlyInstancesOf(Entry::class, $entries);
    }

    /**
     * @throws StreamDeletedException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function get_single_event_from_event_stream(): void
    {
        $streamName = $this->prepareTestStream(1);
        $feed = $this->es->openStreamFeed($streamName);

        [$entry] = $feed->getEntries();
        $eventUrl = $entry->getEventUrl();

        $event = $this->es->readEvent($eventUrl);

        $this->assertSame(0, $event->getVersion());
        $this->assertSame(['foo_data_key' => 'bar'], $event->getData());
        $this->assertNull($event->getMetadata());
    }

    /**
     * @throws StreamDeletedException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function get_single_event_with_provided_event_id(): void
    {
        $eventId = new UUID();
        $streamName = $this->prepareTestStream(1);

        $event = new WritableEvent($eventId, 'Foo_Event', ['foo_data_key' => 'bar']);
        $this->es->writeToStream($streamName, $event);

        $feed = $this->es->openStreamFeed($streamName);
        [$entry] = $feed->getEntries();
        $eventUrl = $entry->getEventUrl();
        $readEvent = $this->es->readEvent($eventUrl);

        $this->assertEquals($eventId, $readEvent->getEventId());
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     * @throws StreamDeletedException
     */
    #[Test]
    public function get_event_batch_from_event_stream(): void
    {
        $streamName = $this->prepareTestStream(20);
        $feed = $this->es->openStreamFeed($streamName);

        $eventUrls = array_map(
            fn (Entry $entry): ?string => $entry->getEventUrl(),
            $feed->getEntries()
        );

        $events = $this->es->readEventBatch($eventUrls);
        $this->assertNotEmpty($events);

        $i = 19;
        foreach ($events as $event) {
            $this->assertSame($i--, $event->getVersion());
            $this->assertInstanceOf(Event::class, $event);
            $this->assertSame(['foo_data_key' => 'bar'], $event->getData());
            $this->assertNull($event->getMetadata());
        }
    }

    /**
     * @throws StreamDeletedException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function get_single_event_with_metadata_from_event_stream(): void
    {
        $metadata = [
            'user' => 'akii',
        ];

        $streamName = $this->prepareTestStream(1, $metadata);
        $feed = $this->es->openStreamFeed($streamName);

        [$entry] = $feed->getEntries();
        $eventUrl = $entry->getEventUrl();

        $event = $this->es->readEvent($eventUrl);

        $this->assertSame(0, $event->getVersion());
        $this->assertSame(['foo_data_key' => 'bar'], $event->getData());
        $this->assertSame($metadata, $event->getMetadata());
    }

    /**
     * @throws UnauthorizedException
     * @throws StreamDeletedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function navigate_stream_using_missing_link_returns_null(): void
    {
        $streamName = $this->prepareTestStream(1);

        $head = $this->es->openStreamFeed($streamName);
        $next = $this->es->navigateStreamFeed($head, LinkRelation::NEXT);

        $this->assertNull($next);
    }

    /**
     * @throws StreamDeletedException
     */
    #[Test]
    public function unexistent_stream_should_throw_not_found_exception(): void
    {
        $this->expectException(StreamNotFoundException::class);
        $this->es->openStreamFeed('this-stream-does-not-exists');
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function deleted_stream_should_throw_an_exception(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->expectException(StreamDeletedException::class);
        $this->es->openStreamFeed($streamName);
    }

    #[Test]
    public function unauthorized_streams_throw_unauthorized_exception(): never
    {
        // I wonder how this worked one day as no $et-Baz stream is ever created
        // For now it throws logically a StreamNotFoundException
        $this->markTestIncomplete('Find a way to create a forbidden stream: create user, change stream acl...');
        // $this->expectException(UnauthorizedException::class);
        // $this->expectExceptionMessage('Tried to open stream http://admin:changeit@127.0.0.1:2113/streams/$et-Baz got 401');
        // $this->es->openStreamFeed('$et-Baz');
    }

    /**
     * @throws UnauthorizedException
     * @throws StreamDeletedException
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     */
    #[Test]
    public function fetching_event_of_a_deleted_stream_throws_an_exception(): void
    {
        $streamName = $this->prepareTestStream(1);
        $feed = $this->es->openStreamFeed($streamName);
        $entries = $feed->getEntries();
        $eventUrl = $entries[0]->getEventUrl();

        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->expectException(StreamDeletedException::class);
        $this->es->readEvent($eventUrl);
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_create_a_forward_iterator(): void
    {
        $streamName = $this->prepareTestStream(1);

        $this->assertEquals(
            StreamFeedIterator::forward(
                $this->es,
                $streamName
            ),
            $this->es->forwardStreamFeedIterator($streamName)
        );
    }

    /**
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_create_a_backward_iterator(): void
    {
        $streamName = $this->prepareTestStream(1);

        $this->assertEquals(
            StreamFeedIterator::backward(
                $this->es,
                $streamName
            ),
            $this->es->backwardStreamFeedIterator($streamName)
        );
    }

    /**
     * @throws ConnectionFailedException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_process_the_all_stream_with_a_forward_iterator(): void
    {
        $client = new Client([
            'auth' => ['admin', 'changeit'],
            'handler' => new CurlMultiHandler(),
        ]);
        $httpClient = new GuzzleHttpClient($client);
        $this->es = $this->createEventStore($httpClient);

        $this->prepareTestStream(1);
        $streamName = rawurlencode('$all');

        $this->assertGreaterThan(
            0,
            iterator_count($this->es->forwardStreamFeedIterator($streamName))
        );
    }

    /**
     * @throws WrongExpectedVersionException
     */
    private function prepareTestStream(int $length = 1, array $metadata = []): string
    {
        $streamName = uniqid();
        $events = [];

        for ($i = 0; $i < $length; ++$i) {
            $events[] = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar'], $metadata);
        }

        $collection = new WritableEventCollection($events);
        $this->es->writeToStream($streamName, $collection);

        return $streamName;
    }
}
