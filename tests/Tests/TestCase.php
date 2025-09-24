<?php

declare(strict_types=1);

namespace KurrentDB\Tests;

use KurrentDB\EventStore;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Http\GuzzleHttpClient;
use KurrentDB\Http\HttpClientInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected EventStore $es;

    /**
     * @throws ConnectionFailedException
     */
    protected function setUp(): void
    {
        $this->es = $this->createEventStore(new GuzzleHttpClient());
    }

    /**
     * @throws ConnectionFailedException
     */
    protected function createEventStore(HttpClientInterface $httpClient): EventStore
    {
        $uri = getenv('EVENTSTORE_URI') ?: 'http://admin:changeit@127.0.0.1:2113';

        return new EventStore($uri, $httpClient);
    }
}
