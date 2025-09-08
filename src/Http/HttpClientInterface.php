<?php

namespace EventStore\Http;

use Psr\Http\Client\ClientInterface;

interface HttpClientInterface extends ClientInterface
{
    public function sendRequestBatch(array $requests): array;
}
