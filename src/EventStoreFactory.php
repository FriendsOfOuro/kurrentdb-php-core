<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Http\ConnectionChecker;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final readonly class EventStoreFactory
{
    /**
     * @throws ConnectionFailedException
     */
    public static function create(
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        ClientInterface $httpClient,
    ): EventStore {
        $connectionChecker = new ConnectionChecker($requestFactory, $httpClient);
        $connectionChecker->checkConnection();

        return new EventStore($uriFactory, $requestFactory, $httpClient);
    }
}
