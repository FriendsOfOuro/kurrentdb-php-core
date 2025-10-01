<?php

declare(strict_types=1);

namespace KurrentDB;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\HttpClientTrait;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\Url\PsrUriHelper;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class StreamReader implements StreamReaderInterface
{
    use HttpClientTrait;

    public function __construct(
        private UriFactoryInterface $uriFactory,
        private RequestFactoryInterface $requestFactory,
        private ClientInterface $httpClient,
        private HttpErrorHandler $errorHandler,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    protected function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }

    protected function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    protected function getErrorHandler(): HttpErrorHandler
    {
        return $this->errorHandler;
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

        return $this->denormalizer->denormalize(
            $this->responseAsJson($response),
            StreamFeed::class,
            context: ['embedMode' => $embedMode]
        );
    }

    /** @param array<string, mixed> $content */
    private function createEventFromResponseContent(array $content): Event
    {
        return $this->denormalizer->denormalize($content, Event::class);
    }
}
