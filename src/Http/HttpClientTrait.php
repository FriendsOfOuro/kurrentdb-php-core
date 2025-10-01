<?php

declare(strict_types=1);

namespace KurrentDB\Http;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

trait HttpClientTrait
{
    abstract protected function getUriFactory(): UriFactoryInterface;

    abstract protected function getRequestFactory(): RequestFactoryInterface;

    abstract protected function getHttpClient(): ClientInterface;

    abstract protected function getErrorHandler(): HttpErrorHandler;

    protected function getStreamUrl(string $streamName): UriInterface
    {
        return $this->getUriFactory()->createUri("/streams/{$streamName}");
    }

    protected function getJsonRequest(UriInterface $uri): RequestInterface
    {
        return $this
            ->getRequestFactory()
            ->createRequest('GET', $uri)
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
    protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->getHttpClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->getErrorHandler()->handleException($request->getUri(), $e);
        }
    }
}
