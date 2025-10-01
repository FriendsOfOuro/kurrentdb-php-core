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
use KurrentDB\Http\HttpClientTrait;
use KurrentDB\Http\HttpErrorHandler;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

final readonly class StreamWriter implements StreamWriterInterface
{
    use HttpClientTrait;

    public function __construct(
        private UriFactoryInterface $uriFactory,
        private RequestFactoryInterface $requestFactory,
        private ClientInterface $httpClient,
        private HttpErrorHandler $errorHandler,
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
        $streamUri = $this->getStreamUrl($streamName);
        $request = $this->requestFactory->createRequest('DELETE', $streamUri);

        if (StreamDeletion::HARD === $mode) {
            $request = $request->withHeader('Kurrent-HardDelete', 'true');
        }

        $response = $this->sendRequest($request);
        $this->errorHandler->handleStatusCode($streamUri, $response);
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
}
