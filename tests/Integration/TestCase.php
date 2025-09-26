<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Integration;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\Guzzle\GuzzleHttpClient;
use FriendsOfOuro\Http\Batch\Guzzle\RecordingHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use KurrentDB\EventStore;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class TestCase extends BaseTestCase
{
    protected EventStore $es;
    protected RecordingHttpClient $recordingHttpClient;

    /**
     * @throws ConnectionFailedException
     */
    protected function setUp(): void
    {
        $this->recordingHttpClient = new RecordingHttpClient(new GuzzleHttpClient());
        $this->es = $this->createEventStore(
            new HttpFactory(),
            $this->recordingHttpClient,
        );
    }

    /**
     * @throws ConnectionFailedException
     */
    protected function createEventStore(RequestFactoryInterface&UriFactoryInterface $factory, ClientInterface $httpClient): EventStore
    {
        $uri = getenv('EVENTSTORE_URI') ?: 'http://admin:changeit@127.0.0.1:2113';

        return new EventStore(new Uri($uri), $factory, $factory, $httpClient);
    }

    /**
     * Prepares a test stream with specified number of events.
     *
     * @param array<string, mixed> $metadata
     *
     * @throws WrongExpectedVersionException
     */
    protected function prepareTestStream(int $length = 1, array $metadata = []): string
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
