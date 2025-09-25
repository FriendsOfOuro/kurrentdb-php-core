<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use KurrentDB\EventStore;
use KurrentDB\Exception\UnauthorizedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Test unauthorized stream access handling.
 */
class UnauthorizedStreamAccessTest extends TestCase
{
    private EventStore $eventStore;
    private ClientInterface&MockObject $mockHttpClient;
    private ResponseInterface&MockObject $mockResponse;
    private StreamInterface&MockObject $mockBody;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockBody = $this->createMock(StreamInterface::class);

        $this->mockBody->method('__toString')->willReturn('');
        $this->mockResponse->method('getBody')->willReturn($this->mockBody);
        $this->mockResponse->method('getStatusCode')->willReturn(401);

        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $factory = new HttpFactory();
        $uri = new Uri('http://admin:changeit@127.0.0.1:2113');

        $this->eventStore = new EventStore($uri, $factory, $factory, $this->mockHttpClient);
    }

    #[Test]
    public function unauthorized_stream_access_throws_unauthorized_exception(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Tried to open stream http://admin:changeit@127.0.0.1:2113/streams/restricted-stream got 401');

        $this->eventStore->openStreamFeed('restricted-stream');
    }
}
