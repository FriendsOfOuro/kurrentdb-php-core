<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Integration;

use FriendsOfOuro\Http\Batch\Guzzle\GuzzleHttpClient;
use FriendsOfOuro\Http\Batch\Guzzle\RecordingHttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\EventStoreFactory;
use KurrentDB\EventStoreInterface;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected EventStoreInterface $es;
    protected RecordingHttpClient $recordingHttpClient;
    protected EventStoreFactory $factory;

    /**
     * @throws ConnectionFailedException
     */
    protected function setUp(): void
    {
        $uri = getenv('EVENTSTORE_URI') ?: 'http://admin:changeit@127.0.0.1:2113';
        $client = new Client([
            'base_uri' => $uri,
            'handler' => new CurlMultiHandler(),
        ]);
        $this->recordingHttpClient = new RecordingHttpClient(new GuzzleHttpClient($client));
        $httpFactory = new HttpFactory();
        $this->factory = new EventStoreFactory($httpFactory, $httpFactory, $this->recordingHttpClient);
        $this->es = $this->factory->create();
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
