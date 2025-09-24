<?php

declare(strict_types=1);

namespace KurrentDB\Tests;

use KurrentDB\EventStore;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\HttpClientInterface;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use KurrentDB\ValueObjects\Identity\UUID;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WriteToStreamErrorHandlingTest extends TestCase
{
    /**
     * @throws WrongExpectedVersionException
     * @throws ConnectionFailedException
     * @throws Exception
     */
    #[Test]
    #[DataProvider('httpErrorCodesProvider')]
    public function write_to_stream_handles_http_errors_correctly(
        int $statusCode,
        ?string $expectedExceptionClass,
        string $description,
    ): void {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);

        if (201 === $statusCode) {
            $mockResponse->method('getHeader')
                ->with('Location')
                ->willReturn(['http://127.0.0.1:2113/streams/test-stream/0']);
        } else {
            $mockResponse->method('getHeader')->willReturn([]);
        }

        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn('');
        $mockResponse->method('getBody')->willReturn($mockBody);

        $mockHttpClient->method('sendRequest')->willReturn($mockResponse);

        $eventStore = new EventStore('http://admin:changeit@127.0.0.1:2113', $mockHttpClient);

        $event = new WritableEvent(
            new UUID(),
            'TestEventType',
            ['test' => 'data']
        );
        $events = new WritableEventCollection([$event]);

        if (null !== $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
        }

        $result = $eventStore->writeToStream('test-stream', $events);

        if (null === $expectedExceptionClass) {
            if (201 === $statusCode) {
                $this->assertIsInt($result, 'Expected integer version for HTTP 201 with Location header');
                $this->assertEquals(0, $result);
            } else {
                $this->assertFalse($result, 'Expected false for successful write without extractable version');
            }
        }
    }

    public static function httpErrorCodesProvider(): array
    {
        return [
            'HTTP 400 Bad Request' => [
                400,
                WrongExpectedVersionException::class,
                'Wrong expected version error',
            ],

            'HTTP 201 Created with version' => [
                201,
                null,
                'Successful write with version extraction',
            ],

            'HTTP 401 Unauthorized' => [
                401,
                UnauthorizedException::class,
                'Authentication failed',
            ],

            'HTTP 404 Not Found' => [
                404,
                StreamNotFoundException::class,
                'Stream not found',
            ],

            'HTTP 500 Internal Server Error' => [
                500,
                ConnectionFailedException::class,
                'Server error (e.g., redirect failure in cluster)',
            ],

            'HTTP 502 Bad Gateway' => [
                502,
                ConnectionFailedException::class,
                'Bad gateway error',
            ],

            'HTTP 503 Service Unavailable' => [
                503,
                ConnectionFailedException::class,
                'Service temporarily unavailable',
            ],

            'HTTP 409 Conflict' => [
                409,
                WrongExpectedVersionException::class,
                'Conflict (alternative to 400 for version mismatch)',
            ],

            'HTTP 410 Gone' => [
                410,
                StreamGoneException::class,
                'Stream has been permanently deleted',
            ],

            'HTTP 429 Too Many Requests' => [
                429,
                ConnectionFailedException::class,
                'Rate limiting',
            ],

            'HTTP 504 Gateway Timeout' => [
                504,
                ConnectionFailedException::class,
                'Gateway timeout',
            ],
        ];
    }

    #[Test]
    public function write_to_stream_with_successful_response_but_no_location_header_returns_false(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(201);
        $mockResponse->method('getHeader')
            ->with('Location')
            ->willReturn([]);

        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn('');
        $mockResponse->method('getBody')->willReturn($mockBody);

        $mockHttpClient->method('sendRequest')->willReturn($mockResponse);

        $eventStore = new EventStore('http://admin:changeit@127.0.0.1:2113', $mockHttpClient);

        $event = new WritableEvent(
            new UUID(),
            'TestEventType',
            ['test' => 'data']
        );

        $result = $eventStore->writeToStream('test-stream', $event);

        $this->assertFalse($result);
    }

    #[Test]
    public function write_to_stream_with_malformed_location_header_returns_false(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(201);
        $mockResponse->method('getHeader')
            ->with('Location')
            ->willReturn(['http://some-other-url/different/path']);

        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn('');
        $mockResponse->method('getBody')->willReturn($mockBody);

        $mockHttpClient->method('sendRequest')->willReturn($mockResponse);

        $eventStore = new EventStore('http://admin:changeit@127.0.0.1:2113', $mockHttpClient);

        $event = new WritableEvent(
            new UUID(),
            'TestEventType',
            ['test' => 'data']
        );

        $result = $eventStore->writeToStream('test-stream', $event);

        $this->assertFalse($result);
    }
}
