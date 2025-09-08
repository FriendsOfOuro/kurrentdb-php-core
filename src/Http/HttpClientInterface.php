<?php

namespace EventStore\Http;

use Http\Client\HttpClient;

interface HttpClientInterface extends HttpClient
{
    public function sendRequestBatch(array $requests): array;
}
