<?php

declare(strict_types=1);

namespace KurrentDB\Http;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client spy that records all requests, responses, and exceptions.
 *
 * This is a decorator/spy pattern implementation that wraps another HTTP client
 * to record all interactions for testing and debugging purposes.
 */
final class RecordingHttpClient implements ClientInterface
{
    /** @var RequestInterface[] */
    private array $requests = [];

    /** @var ResponseInterface[] */
    private array $responses = [];

    /** @var ClientExceptionInterface[] */
    private array $exceptions = [];

    /** @var array<int, array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}> */
    private array $interactions = [];

    public function __construct(private readonly ClientInterface $inner)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        try {
            $response = $this->inner->sendRequest($request);
            $this->responses[] = $response;
            $this->interactions[] = [
                'request' => $request,
                'response' => $response,
            ];

            return $response;
        } catch (ClientExceptionInterface $exception) {
            $this->exceptions[] = $exception;
            $this->interactions[] = [
                'request' => $request,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    public function sendRequestBatch(array $requests): ResponseBatchInterface
    {
        return $this->inner->sendRequestBatch($requests);
    }

    /**
     * Get all recorded requests in chronological order.
     *
     * @return RequestInterface[]
     */
    public function getRecordedRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get all recorded successful responses in chronological order.
     *
     * @return ResponseInterface[]
     */
    public function getRecordedResponses(): array
    {
        return $this->responses;
    }

    /**
     * Get all recorded exceptions in chronological order.
     *
     * @return ClientExceptionInterface[]
     */
    public function getRecordedExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Get all interactions (requests with their responses or exceptions) in chronological order.
     *
     * @return array<int, array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}>
     */
    public function getInteractions(): array
    {
        return $this->interactions;
    }

    /**
     * Get the most recent request, or null if no requests have been made.
     */
    public function getLastRequest(): ?RequestInterface
    {
        return end($this->requests) ?: null;
    }

    /**
     * Get the most recent successful response, or null if no successful responses.
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return end($this->responses) ?: null;
    }

    /**
     * Get the most recent exception, or null if no exceptions occurred.
     */
    public function getLastException(): ?ClientExceptionInterface
    {
        return end($this->exceptions) ?: null;
    }

    /**
     * Get the most recent interaction (request with response or exception).
     *
     * @return array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}|null
     */
    public function getLastInteraction(): ?array
    {
        return end($this->interactions) ?: null;
    }

    /**
     * Get count of recorded requests.
     */
    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    /**
     * Get count of successful responses.
     */
    public function getResponseCount(): int
    {
        return count($this->responses);
    }

    /**
     * Get count of exceptions.
     */
    public function getExceptionCount(): int
    {
        return count($this->exceptions);
    }

    /**
     * Clear all recorded data.
     */
    public function clearRecordings(): void
    {
        $this->requests = [];
        $this->responses = [];
        $this->exceptions = [];
        $this->interactions = [];
    }

    /**
     * Check if any exceptions were recorded.
     */
    public function hasExceptions(): bool
    {
        return [] !== $this->exceptions;
    }

    /**
     * Get requests that resulted in exceptions.
     *
     * @return RequestInterface[]
     */
    public function getFailedRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): RequestInterface => $interaction['request'],
            array_filter(
                $this->interactions,
                fn (array $interaction): bool => isset($interaction['exception'])
            )
        ));
    }

    /**
     * Get requests that resulted in successful responses.
     *
     * @return RequestInterface[]
     */
    public function getSuccessfulRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): RequestInterface => $interaction['request'],
            array_filter(
                $this->interactions,
                fn (array $interaction): bool => isset($interaction['response'])
            )
        ));
    }
}
