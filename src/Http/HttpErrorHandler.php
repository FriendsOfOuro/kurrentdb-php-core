<?php

declare(strict_types=1);

namespace KurrentDB\Http;

use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class HttpErrorHandler
{
    /**
     * Handle HTTP status codes and throw appropriate exceptions.
     *
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws ConnectionFailedException
     */
    public function handleStatusCode(UriInterface $uri, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        switch ($statusCode) {
            case ResponseCode::HTTP_BAD_REQUEST:
                // KurrentDB returns 400 for version conflicts with specific reason phrase
                if ('Wrong expected EventNumber' === $reasonPhrase) {
                    throw new WrongExpectedVersionException();
                }
                throw new BadRequestException(\sprintf('Bad request for stream %s', $uri));
            case ResponseCode::HTTP_UNAUTHORIZED:
                throw new UnauthorizedException(\sprintf('Unauthorized access to stream %s', $uri));
            case ResponseCode::HTTP_NOT_FOUND:
                throw new StreamNotFoundException(\sprintf('Stream %s not found', $uri));
            case ResponseCode::HTTP_GONE:
                throw new StreamGoneException(\sprintf('Stream %s has been permanently deleted', $uri));
            case ResponseCode::HTTP_INTERNAL_SERVER_ERROR:
            case ResponseCode::HTTP_BAD_GATEWAY:
            case ResponseCode::HTTP_SERVICE_UNAVAILABLE:
            case ResponseCode::HTTP_GATEWAY_TIMEOUT:
            case ResponseCode::HTTP_TOO_MANY_REQUESTS:
                throw new ConnectionFailedException(\sprintf('Server error for stream %s: HTTP %d', $uri, $statusCode));
        }
    }

    /**
     * Handle client exceptions and convert them to domain exceptions.
     *
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws WrongExpectedVersionException
     * @throws ConnectionFailedException
     */
    public function handleException(UriInterface $uri, ClientExceptionInterface $exception): void
    {
        if (method_exists($exception, 'getResponse') && null !== $exception->getResponse()) {
            $this->handleStatusCode($uri, $exception->getResponse());
        }

        throw new ConnectionFailedException($exception->getMessage(), $exception->getCode(), $exception);
    }
}
