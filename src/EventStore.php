<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\NoExtractableEventVersionException;
use KurrentDB\Exception\StreamDeletedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\Auth\Credentials;
use KurrentDB\Http\ResponseCode;
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
use Psr\Http\Client\NetworkExceptionInterface;
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

    private readonly array $badCodeHandlers;

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

        $entryFactory = new EntryFactory($this->uriFactory);
        $this->streamFeedFactory = new StreamFeedFactory($this->uriFactory, $entryFactory);

        $this->checkConnection();
        $this->badCodeHandlers = [
            ResponseCode::HTTP_NOT_FOUND => function ($streamUrl): never {
                throw new StreamNotFoundException(\sprintf('No stream found at %s', $streamUrl));
            },

            ResponseCode::HTTP_GONE => function ($streamUrl): never {
                throw new StreamDeletedException(\sprintf('Stream at %s has been permanently deleted', $streamUrl));
            },

            ResponseCode::HTTP_UNAUTHORIZED => function ($streamUrl): never {
                throw new UnauthorizedException(\sprintf('Tried to open stream %s got 401', $streamUrl));
            },
        ];
    }

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream(string $streamName, StreamDeletion $mode): void
    {
        $request = $this->requestFactory->createRequest('DELETE', $this->getStreamUrl($streamName));

        if (StreamDeletion::HARD === $mode) {
            $request = $request->withHeader('ES-HardDelete', 'true');
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
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed
    {
        $url = $streamFeed->getLinkUrl($relation, $this->credentials);

        if (null === $url) {
            return null;
        }

        return $this->readStreamFeed($url, $streamFeed->getEntryEmbedMode());
    }

    /**
     * Opens a stream feed for read and navigation.
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    public function openStreamFeed(string $streamName, EntryEmbedMode $embedMode = EntryEmbedMode::NONE): StreamFeed
    {
        $uri = $this->getStreamUrl($streamName);

        return $this->readStreamFeed($uri, $embedMode);
    }

    /**
     * Read a single event.
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    public function readEvent(string|UriInterface $eventUrl): Event
    {
        $eventUri = $eventUrl instanceof UriInterface ? $eventUrl : $this->uriFactory->createUri($eventUrl);
        $request = $this->getJsonRequest($eventUri);
        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($eventUri);

        $jsonResponse = $this->lastResponseAsJson();

        return $this->createEventFromResponseContent($jsonResponse['content']);
    }

    /**
     * Reads a batch of events.
     */
    public function readEventBatch(array $eventUrls): array
    {
        $requests = array_map(
            fn ($eventUrl): RequestInterface => $this->getJsonRequest($eventUrl),
            $eventUrls
        );

        $responses = $this->httpClient->sendRequestBatch($requests);

        return array_map(
            function ($response): ?Event {
                $data = json_decode((string) $response->getBody(), true);
                if (!isset($data['content'])) {
                    return null;
                }

                return $this->createEventFromResponseContent(
                    $data['content']
                );
            },
            $responses
        );
    }

    public function writeToStream(string $streamName, WritableToStream $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): StreamWriteResult
    {
        if ($events instanceof WritableEvent) {
            $events = new WritableEventCollection([$events]);
        }

        $streamUri = $this->getStreamUrl($streamName);
        $request = $this
            ->requestFactory
            ->createRequest('POST', $streamUri)
            ->withHeader('ES-ExpectedVersion', (string) $expectedVersion)
            ->withHeader('Content-Type', 'application/vnd.kurrent.events+json')
        ;

        foreach ($additionalHeaders as $name => $value) {
            $request = $request->withHeader($name, (string) $value);
        }
        $request->getBody()->write(json_encode($events->toStreamData()));
        $this->sendRequest($request);

        $responseStatusCode = $this->getLastResponse()->getStatusCode();

        // Handle various HTTP error codes
        switch ($responseStatusCode) {
            case ResponseCode::HTTP_BAD_REQUEST:
            case ResponseCode::HTTP_CONFLICT:
                throw new WrongExpectedVersionException();
            case ResponseCode::HTTP_UNAUTHORIZED:
                throw new UnauthorizedException(\sprintf('Unauthorized access to stream %s', $streamUri));
            case ResponseCode::HTTP_NOT_FOUND:
                throw new StreamNotFoundException(\sprintf('Stream %s not found', $streamUri));
            case ResponseCode::HTTP_GONE:
                throw new StreamGoneException(\sprintf('Stream %s has been permanently deleted', $streamUri));
            case ResponseCode::HTTP_INTERNAL_SERVER_ERROR:
            case ResponseCode::HTTP_BAD_GATEWAY:
            case ResponseCode::HTTP_SERVICE_UNAVAILABLE:
            case ResponseCode::HTTP_GATEWAY_TIMEOUT:
            case ResponseCode::HTTP_TOO_MANY_REQUESTS:
                throw new ConnectionFailedException(\sprintf('Server error while writing to stream %s: HTTP %d', $streamUri, $responseStatusCode));
        }

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
        } catch (NetworkExceptionInterface $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    private function getStreamUrl(string $streamName): UriInterface
    {
        $streamUrlString = (string) StreamUrl::fromBaseUrlAndName($this->uri, $streamName);

        return $this->uriFactory->createUri($streamUrlString);
    }

    /**
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
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

    private function getJsonRequest(UriInterface|string $uri): RequestInterface
    {
        return $this
            ->requestFactory
            ->createRequest(
                'GET',
                $this->ensureUri($uri),
            )
            ->withHeader('Accept', 'application/vnd.kurrent.atom+json')
        ;
    }

    private function ensureUri(UriInterface|string $uri): UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        return $this->uriFactory->createUri($uri);
    }

    private function sendRequest(RequestInterface $request): void
    {
        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (\Exception|ClientExceptionInterface $e) {
            if (method_exists($e, 'getResponse')) {
                /* @psalm-suppress PossiblyNullArgument */
                $this->lastResponse = $e->getResponse();
            } else {
                throw new ConnectionFailedException($e->getMessage(), (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    private function ensureStatusCodeIsGood(UriInterface $streamUrl): void
    {
        $code = $this->lastResponse->getStatusCode();

        if (array_key_exists($code, $this->badCodeHandlers)) {
            $this->badCodeHandlers[$code]($streamUrl);
        }
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

    private function lastResponseAsJson(): array
    {
        return json_decode((string) $this->lastResponse->getBody(), true);
    }

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
