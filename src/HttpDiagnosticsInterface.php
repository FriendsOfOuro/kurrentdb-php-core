<?php

declare(strict_types=1);

namespace KurrentDB;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HTTP diagnostics and debugging operations.
 */
interface HttpDiagnosticsInterface
{
    /**
     * Get the response from the last HTTP call to the EventStore API.
     */
    public function getLastResponse(): ResponseInterface;
}
