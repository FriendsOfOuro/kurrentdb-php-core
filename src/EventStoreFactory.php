<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Http\ConnectionChecker;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryDenormalizer;
use KurrentDB\StreamFeed\EventDenormalizer;
use KurrentDB\StreamFeed\LinkDenormalizer;
use KurrentDB\StreamFeed\StreamFeedDenormalizer;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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

        // Create denormalizers (which also act as factories for backward compatibility)
        $linkDenormalizer = new LinkDenormalizer($this->uriFactory);
        $entryDenormalizer = new EntryDenormalizer($linkDenormalizer);
        $streamFeedDenormalizer = new StreamFeedDenormalizer($linkDenormalizer, $entryDenormalizer);

        // Create serializer with all denormalizers
        $serializer = new Serializer(
            [
                new EventDenormalizer(),
                $linkDenormalizer,
                $entryDenormalizer,
                $streamFeedDenormalizer,
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );

        // Create services with injected dependencies
        $streamReader = new StreamReader(
            $this->uriFactory,
            $this->requestFactory,
            $this->httpClient,
            $httpErrorHandler,
            $serializer
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
