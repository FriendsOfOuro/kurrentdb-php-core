<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Integration;

use FriendsOfOuro\Http\Batch\Guzzle\GuzzleHttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\EventStoreFactory;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\StreamDeletion;
use KurrentDB\StreamFeed\Entry;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;

class EventStoreTest extends TestCase
{
    /**
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function client_successfully_connects_to_event_store(): void
    {
        $this->assertMatchesRegularExpression('/^[2-3]\d{2}$/', (string) $this->recordingHttpClient->getLastResponse()->getStatusCode());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function event_is_written_to_stream(): void
    {
        $this->prepareTestStream();

        $this->assertEquals('201', $this->recordingHttpClient->getLastResponse()->getStatusCode());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function version_is_provided_after_writing_to_stream(): void
    {
        $streamName = $this->prepareTestStream();
        $event = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar']);
        $result = $this->es->writeToStream($streamName, WritableEventCollection::of($event));

        $this->assertSame(1, $result->version);
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function wrong_expected_version_should_throw_exception(): void
    {
        $streamName = $this->prepareTestStream();
        $event = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar']);

        $this->expectException(WrongExpectedVersionException::class);
        $this->es->writeToStream($streamName, WritableEventCollection::of($event), 3);
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_is_soft_deleted(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::SOFT);

        $this->assertEquals('204', $this->recordingHttpClient->getLastResponse()->getStatusCode());

        // we try to write to a soft deleted stream...
        $this->es->writeToStream($streamName, WritableEventCollection::of(WritableEvent::newInstance('Foo_Event', ['data' => 'bar'])));

        // ..and we should expect a "201 Created" response
        $this->assertEquals('201', $this->recordingHttpClient->getLastResponse()->getStatusCode());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_is_hard_deleted(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->assertEquals('204', $this->recordingHttpClient->getLastResponse()->getStatusCode());

        // we try to write to a hard deleted stream...
        $this->expectException(StreamGoneException::class);
        $this->es->writeToStream($streamName, WritableEventCollection::of(WritableEvent::newInstance('Foo_Event', ['data' => 'bar'])));
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
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

    /**
     * @throws ConnectionFailedException
     */
    #[Test]
    public function unreacheable_event_store_throws_exception(): void
    {
        $guzzleClient = new Client(['base_uri' => 'http://127.0.0.1:12345/']);
        $httpClient = new GuzzleHttpClient($guzzleClient);
        $this->expectException(ConnectionFailedException::class);
        $f = new HttpFactory();
        new EventStoreFactory($f, $f, $httpClient)->create();
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function get_single_event_with_provided_event_id(): void
    {
        $eventId = new UUID();
        $streamName = $this->prepareTestStream(1);

        $event = new WritableEvent($eventId, 'Foo_Event', ['foo_data_key' => 'bar']);
        $this->es->writeToStream($streamName, WritableEventCollection::of($event));

        $feed = $this->es->openStreamFeed($streamName);
        [$entry] = $feed->getEntries();
        $eventUrl = $entry->getEventUrl();
        $readEvent = $this->es->readEvent($eventUrl);

        $this->assertEquals($eventId, $readEvent->getEventId());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function get_event_batch_from_event_stream(): void
    {
        $streamName = $this->prepareTestStream(20);
        $feed = $this->es->openStreamFeed($streamName);

        $eventUrls = array_filter(array_map(
            fn (Entry $entry): ?UriInterface => $entry->getEventUrl(),
            $feed->getEntries()
        ));

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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
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
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function unexistent_stream_should_throw_not_found_exception(): void
    {
        $this->expectException(StreamNotFoundException::class);
        $this->es->openStreamFeed('this-stream-does-not-exists');
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function deleted_stream_should_throw_an_exception(): void
    {
        $streamName = $this->prepareTestStream();
        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->expectException(StreamGoneException::class);
        $this->es->openStreamFeed($streamName);
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function fetching_event_of_a_deleted_stream_throws_an_exception(): void
    {
        $streamName = $this->prepareTestStream(1);
        $feed = $this->es->openStreamFeed($streamName);
        $entries = $feed->getEntries();
        $eventUrl = $entries[0]->getEventUrl();

        $this->es->deleteStream($streamName, StreamDeletion::HARD);

        $this->expectException(StreamGoneException::class);
        $this->es->readEvent($eventUrl);
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_create_a_forward_iterator(): void
    {
        $streamName = $this->prepareTestStream(1);

        $iterator = $this->es->forwardStreamFeedIterator($streamName);

        // Test that iterator works by iterating over the event
        $iterator->rewind();
        $this->assertTrue($iterator->valid());

        $entryWithEvent = $iterator->current();
        $event = $entryWithEvent->getEvent();
        $this->assertEquals('Foo_Event', $event->getType());
        $this->assertEquals(['foo_data_key' => 'bar'], $event->getData());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_create_a_backward_iterator(): void
    {
        $streamName = $this->prepareTestStream(1);

        $iterator = $this->es->backwardStreamFeedIterator($streamName);

        // Test that iterator works by iterating over the event
        $iterator->rewind();
        $this->assertTrue($iterator->valid());

        $entryWithEvent = $iterator->current();
        $event = $entryWithEvent->getEvent();
        $this->assertEquals('Foo_Event', $event->getType());
        $this->assertEquals(['foo_data_key' => 'bar'], $event->getData());
    }

    /**
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws ConnectionFailedException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function it_can_process_the_all_stream_with_a_forward_iterator(): void
    {
        $this->prepareTestStream(1);
        $streamName = rawurlencode('$all');

        $this->assertGreaterThan(
            0,
            iterator_count($this->es->forwardStreamFeedIterator($streamName))
        );
    }
}
