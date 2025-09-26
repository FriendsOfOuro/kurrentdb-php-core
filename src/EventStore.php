<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\NoExtractableEventVersionException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\ConnectionChecker;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\EntryFactory;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedFactory;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\Url\PsrUriHelper;
use KurrentDB\ValueObjects\Identity\UUID;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class EventStore.
 */
final readonly class EventStore implements EventStoreInterface
{
    private ConnectionChecker $connectionChecker;

    private HttpErrorHandler $errorHandler;

    private StreamFeedFactory $streamFeedFactory;

    /**
     * EventStore constructor.
     *
     * @throws ConnectionFailedException
     */
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private RequestFactoryInterface $requestFactory,
        private ClientInterface $httpClient,
    ) {
        $this->connectionChecker = new ConnectionChecker($this->requestFactory, $this->httpClient);
        $this->errorHandler = new HttpErrorHandler();

        $entryFactory = new EntryFactory($this->uriFactory);
        $this->streamFeedFactory = new StreamFeedFactory($this->uriFactory, $entryFactory);

        $this->connectionChecker->checkConnection();
    }

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     *
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function deleteStream(string $streamName, StreamDeletion $mode): void
    {
        $request = $this->requestFactory->createRequest('DELETE', $this->getStreamUrl($streamName));

        if (StreamDeletion::HARD === $mode) {
            $request = $request->withHeader('Kurrent-HardDelete', 'true');
        }

        $this->sendRequest($request);
    }

    /**
     * Navigates a stream feed through link relations.
     *
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed
    {
        $url = $streamFeed->getLinkUrl($relation);

        if (!$url instanceof UriInterface) {
            return null;
        }

        return $this->readStreamFeed($url, $streamFeed->getEntryEmbedMode());
    }

    /**
     * Opens a stream feed for read and navigation.
     *
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function openStreamFeed(string $streamName, EntryEmbedMode $embedMode = EntryEmbedMode::NONE): StreamFeed
    {
        $uri = $this->getStreamUrl($streamName);

        return $this->readStreamFeed($uri, $embedMode);
    }

    /**
     * Read a single event.
     *
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function readEvent(UriInterface $eventUri): Event
    {
        $request = $this->getJsonRequest($eventUri);
        $response = $this->sendRequest($request);

        $this->errorHandler->handleStatusCode($eventUri, $response);

        $jsonResponse = $this->responseAsJson($response);

        return $this->createEventFromResponseContent($jsonResponse['content']);
    }

    /**
     * Reads a batch of events.
     *
     * @throws ClientExceptionInterface
     */
    public function readEventBatch(array $eventUrls): array
    {
        $requests = array_map(
            fn (UriInterface $eventUrl): RequestInterface => $this->getJsonRequest($eventUrl),
            $eventUrls
        );

        $batch = $this->httpClient->sendRequestBatch($requests);

        // Fail fast if any request failed - smart retry will be implemented later
        if ($batch->hasAnyFailures()) {
            $exceptions = $batch->getExceptions();
            throw $exceptions[0];
        }

        // Process all successful responses
        return array_filter(array_map(
            function (ResponseInterface $response): ?Event {
                $data = json_decode((string) $response->getBody(), true);
                if (!isset($data['content'])) {
                    return null;
                }

                return $this->createEventFromResponseContent(
                    $data['content']
                );
            },
            $batch->getResponses()
        ));
    }

    /**
     * @param array<string, string> $additionalHeaders
     *
     * @throws BadRequestException
     * @throws ConnectionFailedException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     */
    public function writeToStream(string $streamName, WritableToStream $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): StreamWriteResult
    {
        if ($events instanceof WritableEvent) {
            $events = new WritableEventCollection([$events]);
        }

        $streamUri = $this->getStreamUrl($streamName);
        $request = $this
            ->requestFactory
            ->createRequest('POST', $streamUri)
            ->withHeader('Kurrent-ExpectedVersion', (string) $expectedVersion)
            ->withHeader('Content-Type', 'application/vnd.kurrent.events+json')
        ;

        foreach ($additionalHeaders as $name => $value) {
            $request = $request->withHeader($name, (string) $value);
        }
        $request->getBody()->write((string) json_encode($events->toStreamData()));
        $response = $this->sendRequest($request);

        $this->errorHandler->handleStatusCode($streamUri, $response);

        $version = $this->extractStreamVersionFromResponse($response);

        return new StreamWriteResult($version);
    }

    public function forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return StreamFeedIterator::forward($this, $streamName, $pageLimit);
    }

    public function backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX): StreamFeedIterator
    {
        return StreamFeedIterator::backward($this, $streamName, $pageLimit);
    }

    private function getStreamUrl(string $streamName): UriInterface
    {
        return $this->uriFactory->createUri("/streams/{$streamName}");
    }

    /**
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    private function readStreamFeed(UriInterface $streamUri, EntryEmbedMode $embedMode): StreamFeed
    {
        $request = $this->getJsonRequest($streamUri);

        if (EntryEmbedMode::NONE !== $embedMode) {
            $uri = PsrUriHelper::withQueryValue(
                $request->getUri(),
                'embed',
                $embedMode->value
            );

            $request = $request->withUri($uri);
        }

        $response = $this->sendRequest($request);

        $this->errorHandler->handleStatusCode($streamUri, $response);

        return $this->streamFeedFactory->create(
            $this->responseAsJson($response),
            $embedMode,
        );
    }

    private function getJsonRequest(UriInterface $uri): RequestInterface
    {
        return $this
            ->requestFactory
            ->createRequest(
                'GET',
                $uri,
            )
            ->withHeader('Accept', 'application/vnd.kurrent.atom+json')
        ;
    }

    /**
     * @throws BadRequestException
     * @throws ConnectionFailedException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->errorHandler->handleException($request->getUri(), $e);
        }
    }

    /**
     * Extracts created version after writing to a stream.
     *
     * The Event Store responds with a HTTP message containing a Location
     * header pointing to the newly created stream. This method extracts
     * the last part of that URI and returns the value.
     *
     * http://127.0.0.1:2113/streams/newstream/13 -> 13
     *
     * @throws NoExtractableEventVersionException
     */
    private function extractStreamVersionFromResponse(ResponseInterface $response): int
    {
        $locationHeaders = $response->getHeader('Location');

        if (!isset($locationHeaders[0])) {
            throw new NoExtractableEventVersionException();
        }

        $pathComponents = explode('/', $locationHeaders[0]);
        $version = end($pathComponents);

        if (false === $version || !is_numeric($version)) {
            throw new NoExtractableEventVersionException();
        }

        return (int) $version;
    }

    /** @return array<string, mixed> */
    private function responseAsJson(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }

    /** @param array<string, mixed> $content */
    private function createEventFromResponseContent(array $content): Event
    {
        $type = $content['eventType'];
        $version = (int) $content['eventNumber'];
        $data = $content['data'];
        $metadata = (empty($content['metadata'])) ? null : $content['metadata'];
        $eventId = (empty($content['eventId']) ? null : UUID::fromNative($content['eventId']));

        return new Event($type, $version, $data, $metadata, $eventId);
    }
}
