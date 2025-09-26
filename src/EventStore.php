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
use KurrentDB\Http\Auth\Credentials;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\EntryFactory;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedFactory;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\StreamFeed\StreamUrl;
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
final class EventStore implements EventStoreInterface
{
    private readonly Credentials $credentials;

    private readonly HttpErrorHandler $errorHandler;

    private readonly StreamFeedFactory $streamFeedFactory;

    private ResponseInterface $lastResponse;

    /**
     * EventStore constructor.
     *
     * @throws ConnectionFailedException
     */
    public function __construct(
        private readonly UriInterface $uri,
        private readonly UriFactoryInterface $uriFactory,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ClientInterface $httpClient,
    ) {
        $this->credentials = Credentials::fromString($this->uri->getUserInfo());
        $this->errorHandler = new HttpErrorHandler();

        $entryFactory = new EntryFactory($this->uriFactory);
        $this->streamFeedFactory = new StreamFeedFactory($this->uriFactory, $entryFactory);

        $this->checkConnection();
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
     * Get the response from the last HTTP call to the EventStore API.
     */
    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
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
        $url = $streamFeed->getLinkUrl($relation, $this->credentials);

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
        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($eventUri);

        $jsonResponse = $this->lastResponseAsJson();

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

        $responses = $this->httpClient->sendRequestBatch($requests);

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
            $responses
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
        $this->sendRequest($request);

        $this->errorHandler->handleStatusCode($streamUri, $this->getLastResponse());

        $version = $this->extractStreamVersionFromLastResponse($streamUri);

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

    /**
     * @throws ConnectionFailedException
     */
    private function checkConnection(): void
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $this->uri);
            $this->sendRequest($request);
        } catch (\Exception $e) {
            throw new ConnectionFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function getStreamUrl(string $streamName): UriInterface
    {
        $streamUrlString = (string) StreamUrl::fromBaseUrlAndName($this->uri, $streamName);

        return $this->uriFactory->createUri($streamUrlString);
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

        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($streamUri);

        return $this->streamFeedFactory->create(
            $this->lastResponseAsJson(),
            $embedMode,
            $this->credentials,
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
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    private function sendRequest(RequestInterface $request): void
    {
        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->saveLastResponse($e);
            $this->errorHandler->handleException($request->getUri(), $e);
        }
    }

    /**
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    private function ensureStatusCodeIsGood(UriInterface $streamUrl): void
    {
        $this->errorHandler->handleStatusCode($streamUrl, $this->lastResponse);
    }

    /**
     * Extracts created version after writing to a stream.
     *
     * The Event Store responds with a HTTP message containing a Location
     * header pointing to the newly created stream. This method extracts
     * the last part of that URI an returns the value.
     *
     * http://127.0.0.1:2113/streams/newstream/13 -> 13
     *
     * @throws NoExtractableEventVersionException
     */
    private function extractStreamVersionFromLastResponse(UriInterface $streamUri): int
    {
        $locationHeaders = $this->getLastResponse()->getHeader('Location');

        if (!isset($locationHeaders[0])) {
            throw new NoExtractableEventVersionException();
        }

        $streamUri = $streamUri->withUserInfo('');

        if (!preg_match('#^'.preg_quote((string) $streamUri).'/([^/]+)$#', $locationHeaders[0], $matches)) {
            throw new NoExtractableEventVersionException();
        }

        return (int) $matches[1];
    }

    /** @return array<string, mixed> */
    private function lastResponseAsJson(): array
    {
        return json_decode((string) $this->lastResponse->getBody(), true);
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

    public function saveLastResponse(ClientExceptionInterface $e): void
    {
        if (method_exists($e, 'getResponse') && null !== $e->getResponse()) {
            $this->lastResponse = $e->getResponse();
        }
    }
}
