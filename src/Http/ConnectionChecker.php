<?php

declare(strict_types=1);

namespace KurrentDB\Http;

use FriendsOfOuro\Http\Batch\ClientInterface;
use KurrentDB\Exception\ConnectionFailedException;
use Psr\Http\Message\RequestFactoryInterface;

final readonly class ConnectionChecker
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private ClientInterface $httpClient,
    ) {
    }

    /**
     * @throws ConnectionFailedException
     */
    public function checkConnection(): void
    {
        try {
            $request = $this->requestFactory->createRequest('GET', '/');
            $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            throw new ConnectionFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
