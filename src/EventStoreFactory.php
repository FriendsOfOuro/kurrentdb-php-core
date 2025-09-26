<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Http\ConnectionChecker;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryFactory;
use KurrentDB\StreamFeed\StreamFeedFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final readonly class EventStoreFactory implements EventStoreFactoryInterface
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private RequestFactoryInterface $requestFactory,
        private ClientInterface $httpClient,
    ) {
    }

    /**
     * @throws ConnectionFailedException
     */
    public function create(): EventStoreInterface
    {
        $connectionChecker = new ConnectionChecker($this->requestFactory, $this->httpClient);
        $connectionChecker->checkConnection();

        // Create shared dependencies
        $httpErrorHandler = new HttpErrorHandler();
        $entryFactory = new EntryFactory($this->uriFactory);
        $streamFeedFactory = new StreamFeedFactory($this->uriFactory, $entryFactory);

        // Create services with injected dependencies
        $streamReader = new StreamReader(
            $this->uriFactory,
            $this->requestFactory,
            $this->httpClient,
            $httpErrorHandler,
            $streamFeedFactory
        );

        $streamWriter = new StreamWriter(
            $this->uriFactory,
            $this->requestFactory,
            $this->httpClient,
            $httpErrorHandler
        );

        $streamIteratorFactory = new StreamIteratorFactory($streamReader);

        return new EventStore($streamReader, $streamWriter, $streamIteratorFactory);
    }
}
