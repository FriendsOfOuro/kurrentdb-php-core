<?php

namespace EventStore;

use EventStore\Exception\ConnectionFailedException;
use EventStore\Exception\NoExtractableEventVersionException;
use EventStore\Exception\StreamDeletedException;
use EventStore\Exception\StreamNotFoundException;
use EventStore\Exception\UnauthorizedException;
use EventStore\Exception\WrongExpectedVersionException;
use EventStore\Http\HttpClientInterface;
use EventStore\Http\ResponseCode;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use EventStore\StreamFeed\StreamFeedIterator;
use EventStore\StreamFeed\StreamUrl;
use EventStore\ValueObjects\Identity\UUID;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EventStore.
 */
final class EventStore implements EventStoreInterface
{
    private readonly array $urlParts;

    private readonly array $badCodeHandlers;

    private ResponseInterface $lastResponse;

    /**
     * EventStore constructor.
     *
     * @throws ConnectionFailedException
     */
    public function __construct(private readonly string $url, private readonly HttpClientInterface $httpClient)
    {
        $urlParts = parse_url($url);
        if (!is_array($urlParts)) {
            throw new \InvalidArgumentException(sprintf('URL %s is not valid', $url));
        }
        $this->urlParts = $urlParts;

        $this->checkConnection();
        $this->initBadCodeHandlers();
    }

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream(string $streamName, StreamDeletion $mode): void
    {
        $request = new Request('DELETE', $this->getStreamUrl($streamName));

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
        $url = $streamFeed->getLinkUrl($relation, [
            'user' => $this->urlParts['user'],
            'pass' => $this->urlParts['pass'],
        ]);

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
    public function openStreamFeed(string $streamName, ?EntryEmbedMode $embedMode = null): StreamFeed
    {
        $url = $this->getStreamUrl($streamName);

        return $this->readStreamFeed($url, $embedMode);
    }

    /**
     * Read a single event.
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    public function readEvent(string $eventUrl): Event
    {
        $request = $this->getJsonRequest($eventUrl);
        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($eventUrl);

        $jsonResponse = $this->lastResponseAsJson();

        return $this->createEventFromResponseContent($jsonResponse['content']);
    }

    /**
     * Reads a batch of events.
     */
    public function readEventBatch(array $eventUrls): array
    {
        $requests = array_map(
            fn ($eventUrl): Request => $this->getJsonRequest($eventUrl),
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

    private function createEventFromResponseContent(array $content): Event
    {
        $type = $content['eventType'];
        $version = (int) $content['eventNumber'];
        $data = $content['data'];
        $metadata = (empty($content['metadata'])) ? null : $content['metadata'];
        $eventId = (empty($content['eventId']) ? null : UUID::fromNative($content['eventId']));

        return new Event($type, $version, $data, $metadata, $eventId);
    }

    public function writeToStream(string $streamName, WritableToStream $events, int $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = []): false|int
    {
        if ($events instanceof WritableEvent) {
            $events = new WritableEventCollection([$events]);
        }

        $streamUrl = $this->getStreamUrl($streamName);
        $headers = [
            'ES-ExpectedVersion' => $expectedVersion,
            'Content-Type' => 'application/vnd.eventstore.events+json',
            'Content-Length' => 0,
        ];

        $headers = $additionalHeaders + $headers;
        $request = new Request(
            'POST',
            $streamUrl,
            $headers,
            json_encode($events->toStreamData())
        );

        $this->sendRequest($request);

        $responseStatusCode = $this->getLastResponse()->getStatusCode();

        if (ResponseCode::HTTP_BAD_REQUEST == $responseStatusCode) {
            throw new WrongExpectedVersionException();
        }

        try {
            return $this->extractStreamVersionFromLastResponse($streamUrl);
        } catch (NoExtractableEventVersionException) {
            return false;
        }
    }

    public function forwardStreamFeedIterator(string $streamName): StreamFeedIterator
    {
        return StreamFeedIterator::forward($this, $streamName);
    }

    public function backwardStreamFeedIterator(string $streamName): StreamFeedIterator
    {
        return StreamFeedIterator::backward($this, $streamName);
    }

    /**
     * @throws ConnectionFailedException
     */
    private function checkConnection(): void
    {
        try {
            $request = new Request('GET', $this->url);
            $this->sendRequest($request);
        } catch (ConnectException $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    private function getStreamUrl(string $streamName): string
    {
        return (string) StreamUrl::fromBaseUrlAndName($this->url, $streamName);
    }

    private function removeCredentialsFromUrl(string $url): string
    {
        $parts = parse_url($url);
        unset($parts['user']);
        unset($parts['pass']);

        return \unparse_url($parts);
    }

    /**
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    private function readStreamFeed(string $streamUrl, ?EntryEmbedMode $embedMode = null): StreamFeed
    {
        $request = $this->getJsonRequest($streamUrl);

        if (null !== $embedMode && EntryEmbedMode::NONE !== $embedMode) {
            $uri = Uri::withQueryValue(
                $request->getUri(),
                'embed',
                $embedMode->value
            );

            $request = $request->withUri($uri);
        }

        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($streamUrl);

        return new StreamFeed(
            $this->lastResponseAsJson(),
            $embedMode,
            [
                'user' => $this->urlParts['user'],
                'pass' => $this->urlParts['pass'],
            ]
        );
    }

    private function getJsonRequest(string $uri): Request
    {
        return new Request(
            'GET',
            $uri,
            [
                'Accept' => 'application/vnd.eventstore.atom+json',
            ]
        );
    }

    private function sendRequest(RequestInterface $request): void
    {
        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (HttpException $e) {
            $this->lastResponse = $e->getResponse();
        }
    }

    /**
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    private function ensureStatusCodeIsGood(string $streamUrl): void
    {
        $code = $this->lastResponse->getStatusCode();

        if (array_key_exists($code, $this->badCodeHandlers)) {
            $this->badCodeHandlers[$code]($streamUrl);
        }
    }

    private function initBadCodeHandlers(): void
    {
        $this->badCodeHandlers = [
            ResponseCode::HTTP_NOT_FOUND => function ($streamUrl): never {
                throw new StreamNotFoundException(sprintf('No stream found at %s', $streamUrl));
            },

            ResponseCode::HTTP_GONE => function ($streamUrl): never {
                throw new StreamDeletedException(sprintf('Stream at %s has been permanently deleted', $streamUrl));
            },

            ResponseCode::HTTP_UNAUTHORIZED => function ($streamUrl): never {
                throw new UnauthorizedException(sprintf('Tried to open stream %s got 401', $streamUrl));
            },
        ];
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
    private function extractStreamVersionFromLastResponse(string $streamUrl): int
    {
        $locationHeaders = $this->getLastResponse()->getHeader('Location');

        if (!isset($locationHeaders[0])) {
            throw new NoExtractableEventVersionException();
        }

        $streamUrl = $this->removeCredentialsFromUrl($streamUrl);

        if (!preg_match('#^'.preg_quote($streamUrl).'/([^/]+)$#', $locationHeaders[0], $matches)) {
            throw new NoExtractableEventVersionException();
        }

        return (int) $matches[1];
    }

    private function lastResponseAsJson(): array
    {
        return json_decode($this->lastResponse->getBody(), true);
    }
}
