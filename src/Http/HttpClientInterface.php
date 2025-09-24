<?php

declare(strict_types=1);

namespace KurrentDB\Http;

use Psr\Http\Client\ClientInterface;

interface HttpClientInterface extends ClientInterface
{
    public function sendRequestBatch(array $requests): array;
}
