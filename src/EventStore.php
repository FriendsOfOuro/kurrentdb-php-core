<?php
namespace EventStore;

use EventStore\Exception\ConnectionFailedException;
use EventStore\Exception\StreamDeletedException;
use EventStore\Exception\StreamNotFoundException;
use EventStore\Exception\UnauthorizedException;
use EventStore\Exception\WrongExpectedVersionException;
use EventStore\Exception\NoExtractableEventVersionException;
use EventStore\Http\Exception\ClientException;
use EventStore\Http\Exception\RequestException;

use EventStore\Http\HttpClientInterface;
use EventStore\Http\ResponseCode;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use EventStore\StreamFeed\StreamFeedIterator;
use EventStore\ValueObjects\Identity\UUID;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use \Http\Client\Exception\HttpException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EventStore.
 */
final class EventStore implements EventStoreInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $urlParts;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @var array
     */
    private $badCodeHandlers = [];

    /**
     * EventStore constructor.
     *
     * @param string              $url
     * @param HttpClientInterface $httpClient
     *
     * @throws ConnectionFailedException
     */
    public function __construct(string $url, HttpClientInterface $httpClient)
    {
        $this->urlParts = parse_url($url);
        $this->url = $url;
        $this->httpClient = $httpClient;

        $this->checkConnection();
        $this->initBadCodeHandlers();
    }

    /**
     * Delete a stream.
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream($streamName, StreamDeletion $mode): void
    {
        $request = new Request('DELETE', $this->getStreamUrl($streamName));

        if (StreamDeletion::HARD == $mode) {
            $request = $request->withHeader('ES-HardDelete', 'true');
        }

        $this->sendRequest($request);
    }

    /**
     * Get the response from the last HTTP call to the EventStore API.
     *
     * @return ResponseInterface
     */
    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Navigates a stream feed through link relations.
     *
     * @param StreamFeed   $streamFeed
     * @param LinkRelation $relation
     *
     * @return StreamFeed|null
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation): ?StreamFeed
    {
        $url = $streamFeed->getLinkUrl($relation, [
            'user' => $this->urlParts['user'],
            'pass' => $this->urlParts['pass'],
        ]);

        if (empty($url)) {
            return null;
        }

        return $this->readStreamFeed($url, $streamFeed->getEntryEmbedMode());
    }

    /**
     * Opens a stream feed for read and navigation.
     *
     * @param string              $streamName
     * @param EntryEmbedMode|null $embedMode
     *
     * @return StreamFeed
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     */
    public function openStreamFeed($streamName, EntryEmbedMode $embedMode = null): StreamFeed
    {
        $url = $this->getStreamUrl($streamName);

        return $this->readStreamFeed($url, $embedMode);
    }

    /**
     * Read a single event.
     *
     * @param string $eventUrl
     *
     * @return Event
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    public function readEvent($eventUrl): Event
    {
        $request = $this->getJsonRequest($eventUrl);
        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($eventUrl);

        $jsonResponse = $this->lastResponseAsJson();

        return $this->createEventFromResponseContent($jsonResponse['content']);
    }

    /**
     * Reads a batch of events.
     *
     * @param array $eventUrls
     *
     * @return array
     */
    public function readEventBatch(array $eventUrls): array
    {
        $requests = array_map(
            function ($eventUrl) {
                return $this->getJsonRequest($eventUrl);
            },
            $eventUrls
        );

        $responses = $this->httpClient->sendRequestBatch($requests);

        return array_map(
            function ($response) {
                $data = json_decode($response->getBody(), true);
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

    /**
     * @param array $content
     *
     * @return Event
     */
    private function createEventFromResponseContent(array $content): Event
    {
        $type = $content['eventType'];
        $version = (int) $content['eventNumber'];
        $data = $content['data'];
        $metadata = (!empty($content['metadata'])) ? $content['metadata'] : null;
        $eventId = (!empty($content['eventId']) ? UUID::fromNative($content['eventId']) : null);

        return new Event($type, $version, $data, $metadata, $eventId);
    }

    /**
     * {@inheritdoc}
     */
    public function writeToStream($streamName, WritableToStream $events, $expectedVersion = ExpectedVersion::ANY, array $additionalHeaders = [])
    {
        if ($events instanceof WritableEvent) {
            $events = new WritableEventCollection([$events]);
        }

        $streamUrl = $this->getStreamUrl($streamName);
        $headers = [
            'ES-ExpectedVersion' => intval($expectedVersion),
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
        } catch (NoExtractableEventVersionException $e) {
            return false;
        }

    }

    /**
     * @param string $streamName
     *
     * @return StreamFeedIterator
     */
    public function forwardStreamFeedIterator($streamName): StreamFeedIterator
    {
        return StreamFeedIterator::forward($this, $streamName);
    }

    /**
     * @param string $streamName
     *
     * @return StreamFeedIterator
     */
    public function backwardStreamFeedIterator($streamName): StreamFeedIterator
    {
        return StreamFeedIterator::backward($this, $streamName);
    }

    /**
     * @throws Exception\ConnectionFailedException
     */
    private function checkConnection(): void
    {
        try {
            $request = new Request('GET', $this->url);
            $this->sendRequest($request);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    /**
     * @param string $streamName
     *
     * @return string
     */
    private function getStreamUrl(string $streamName): string
    {
        return sprintf('%s/streams/%s', $this->url, $streamName);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function addCredentialsToUrl(string $url): string
    {
        $parts = parse_url($url);
        $parts['user'] = $this->credentials['user'];
        $parts['pass'] = $this->credentials['pass'];
        $newUrl = http_build_query($parts);
        var_dump($newUrl);
        exit;
        return $newUrl;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function removeCredentialsFromUrl(string $url): string
    {
        $parts = parse_url($url);
        unset($parts['user']);
        unset($parts['pass']);
        $newUrl = \unparse_url($parts);
        return $newUrl;
    }

    /**
     * @param $streamUrl
     * @param EntryEmbedMode|null $embedMode
     *
     * @return StreamFeed
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    private function readStreamFeed($streamUrl, EntryEmbedMode $embedMode = null): StreamFeed
    {
        $request = $this->getJsonRequest($streamUrl);

        if (null != $embedMode && $embedMode != EntryEmbedMode::NONE()) {
            $uri = Uri::withQueryValue(
                $request->getUri(),
                'embed',
                $embedMode->toNative()
            );

            $request = $request->withUri($uri);
        }

        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($streamUrl);

        return new StreamFeed(
            $this->lastResponseAsJson(),
            $embedMode, [
                'user' => $this->urlParts['user'],
                'pass' => $this->urlParts['pass'],
            ]
        );
    }

    /**
     * @param string $uri
     *
     * @return RequestInterface
     */
    private function getJsonRequest(string $uri)
    {
        return new Request(
            'GET',
            $uri,
            [
                'Accept' => 'application/vnd.eventstore.atom+json',
            ]
        );
    }

    /**
     * @param RequestInterface $request
     */
    private function sendRequest(RequestInterface $request): void
    {
        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (\Http\Client\Exception\HttpException $e) {
            $this->lastResponse = $e->getResponse();
        }
    }

    /**
     * @param string $streamUrl
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
            ResponseCode::HTTP_NOT_FOUND => function ($streamUrl) {
                throw new StreamNotFoundException(
                        sprintf(
                            'No stream found at %s',
                            $streamUrl
                        )
                    );
            },

            ResponseCode::HTTP_GONE => function ($streamUrl) {
                throw new StreamDeletedException(
                        sprintf(
                            'Stream at %s has been permanently deleted',
                            $streamUrl
                        )
                    );
            },

            ResponseCode::HTTP_UNAUTHORIZED => function ($streamUrl) {
                throw new UnauthorizedException(
                        sprintf(
                            'Tried to open stream %s got 401',
                            $streamUrl
                        )
                    );
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
     * @param string $streamUrl
     * @throws NoExtractableEventVersionException
     *
     * @return int
     */
    private function extractStreamVersionFromLastResponse(string $streamUrl)
    {
        $locationHeaders = $this->getLastResponse()->getHeader('Location');

        if (!isset($locationHeaders[0])) {
            throw new NoExtractableEventVersionException;
        }

        $streamUrl = $this->removeCredentialsFromUrl($streamUrl);

        if (!preg_match("#^".preg_quote($streamUrl)."/([^/]+)$#", $locationHeaders[0], $matches)) {
            throw new NoExtractableEventVersionException;
        }

        return (int) $matches[1];
    }

    /**
     * @return array
     */
    private function lastResponseAsJson(): array
    {
        return json_decode($this->lastResponse->getBody(), true);
    }
}
