<?php

declare(strict_types=1);

namespace KurrentDB\Tests;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\Guzzle\GuzzleHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\EventStore;
use KurrentDB\Exception\ConnectionFailedException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class TestCase extends BaseTestCase
{
    protected EventStore $es;

    /**
     * @throws ConnectionFailedException
     */
    protected function setUp(): void
    {
        $this->es = $this->createEventStore(
            new HttpFactory(),
            new GuzzleHttpClient(),
        );
    }

    /**
     * @throws ConnectionFailedException
     */
    protected function createEventStore(RequestFactoryInterface&UriFactoryInterface $factory, ClientInterface $httpClient): EventStore
    {
        $uri = getenv('EVENTSTORE_URI') ?: 'http://admin:changeit@127.0.0.1:2113';

        return new EventStore($uri, $factory, $factory, $httpClient);
    }
}
