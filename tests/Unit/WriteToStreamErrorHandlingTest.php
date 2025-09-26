<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\EventStore;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\NoExtractableEventVersionException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class WriteToStreamErrorHandlingTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private ResponseInterface&MockObject $mockResponse;
    private StreamInterface&MockObject $mockBody;
    private EventStore $eventStore;
    private WritableEvent $testEvent;

    /**
     * @throws MockException
     * @throws ConnectionFailedException
     */
    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockBody = $this->createMock(StreamInterface::class);

        $this->mockBody->method('__toString')->willReturn('');
        $this->mockResponse->method('getBody')->willReturn($this->mockBody);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $httpFactory = new HttpFactory();
        $this->eventStore = new EventStore(
            $httpFactory,
            $httpFactory,
            $this->mockHttpClient
        );

        $this->testEvent = new WritableEvent(
            new UUID(),
            'TestEventType',
            ['test' => 'data']
        );
    }

    private function configureResponseStatusCode(int $statusCode, string $reasonPhrase = ''): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn($statusCode);
        $this->mockResponse->method('getReasonPhrase')->willReturn($reasonPhrase);
    }

    /** @param string[] $headers */
    private function configureLocationHeader(array $headers): void
    {
        $this->mockResponse->method('getHeader')
            ->with('Location')
            ->willReturn($headers)
        ;
    }

    private function configureMissingLocationHeader(): void
    {
        $this->configureResponseStatusCode(201);
        $this->configureLocationHeader([]);
    }

    private function configureMalformedLocationHeader(): void
    {
        $this->configureResponseStatusCode(201);
        $this->configureLocationHeader(['http://some-other-url/different/path']);
    }

    /**
     * @param class-string<\Throwable>|null $expectedExceptionClass
     */
    #[Test]
    #[DataProvider('httpErrorCodesProvider')]
    public function write_to_stream_handles_http_errors_correctly(
        int $statusCode,
        ?string $expectedExceptionClass,
        string $description,
        ?string $reasonPhrase = null,
    ): void {
        $this->configureResponseStatusCode($statusCode, $reasonPhrase ?? '');

        if (201 === $statusCode) {
            $this->configureLocationHeader(['http://127.0.0.1:2113/streams/test-stream/0']);
        } else {
            $this->configureLocationHeader([]);
        }

        $events = new WritableEventCollection([$this->testEvent]);

        if (null !== $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
        }

        $result = $this->eventStore->writeToStream('test-stream', $events);

        if (null === $expectedExceptionClass && 201 === $statusCode) {
            $this->assertEquals(0, $result->version);
        }
    }

    /** @return array<string, array<mixed>> */
    public static function httpErrorCodesProvider(): array
    {
        return [
            // Test key error scenarios and successful case
            'HTTP 400 Bad Request' => [
                400,
                BadRequestException::class,
                'Bad request error',
            ],

            'HTTP 400 Version Conflict' => [
                400,
                WrongExpectedVersionException::class,
                'Version conflict (KurrentDB specific)',
                'Wrong expected EventNumber',
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

            // Test one server error case to ensure integration works
            'HTTP 500 Internal Server Error' => [
                500,
                ConnectionFailedException::class,
                'Server error (e.g., redirect failure in cluster)',
            ],

            'HTTP 410 Gone' => [
                410,
                StreamGoneException::class,
                'Stream has been permanently deleted',
            ],
        ];
    }

    #[Test]
    public function write_to_stream_with_successful_response_but_no_location_header_throws_exception(): void
    {
        $this->configureMissingLocationHeader();

        $this->expectException(NoExtractableEventVersionException::class);
        $this->eventStore->writeToStream('test-stream', $this->testEvent);
    }

    #[Test]
    public function write_to_stream_with_malformed_location_header_throws_exception(): void
    {
        $this->configureMalformedLocationHeader();

        $this->expectException(NoExtractableEventVersionException::class);
        $this->eventStore->writeToStream('test-stream', $this->testEvent);
    }
}
