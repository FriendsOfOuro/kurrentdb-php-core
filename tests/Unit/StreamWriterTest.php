<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\Exception\NoExtractableEventVersionException;
use KurrentDB\ExpectedVersion;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamDeletion;
use KurrentDB\StreamWriter;
use KurrentDB\StreamWriteResult;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class StreamWriterTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private ResponseInterface&MockObject $mockResponse;
    private StreamInterface&MockObject $mockBody;
    private StreamWriter $streamWriter;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockBody = $this->createMock(StreamInterface::class);

        $this->mockResponse->method('getBody')->willReturn($this->mockBody);

        $httpFactory = new HttpFactory();
        $httpErrorHandler = new HttpErrorHandler();
        $this->streamWriter = new StreamWriter(
            $httpFactory,
            $httpFactory,
            $this->mockHttpClient,
            $httpErrorHandler
        );
    }

    #[Test]
    public function write_to_stream_with_single_event_returns_write_result(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->with('Location')->willReturn(['http://example.com/streams/test-stream/0']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);
        $result = $this->streamWriter->writeToStream('test-stream', $event);

        $this->assertInstanceOf(StreamWriteResult::class, $result);
        $this->assertEquals(0, $result->version);
    }

    #[Test]
    public function write_to_stream_with_event_collection_returns_write_result(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->with('Location')->willReturn(['http://example.com/streams/test-stream/1']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $events = new WritableEventCollection([
            new WritableEvent(new UUID(), 'TestEvent1', ['test' => 'data1']),
            new WritableEvent(new UUID(), 'TestEvent2', ['test' => 'data2']),
        ]);

        $result = $this->streamWriter->writeToStream('test-stream', $events, ExpectedVersion::ANY);

        $this->assertInstanceOf(StreamWriteResult::class, $result);
        $this->assertEquals(1, $result->version);
    }

    #[Test]
    public function write_to_stream_throws_exception_when_no_location_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->with('Location')->willReturn([]);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);

        $this->expectException(NoExtractableEventVersionException::class);

        $this->streamWriter->writeToStream('test-stream', $event);
    }

    #[Test]
    public function write_to_stream_throws_exception_when_malformed_location_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->with('Location')->willReturn(['http://example.com/invalid/path']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);

        $this->expectException(NoExtractableEventVersionException::class);

        $this->streamWriter->writeToStream('test-stream', $event);
    }

    #[Test]
    public function delete_stream_sends_delete_request(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(204);
        $this->mockHttpClient->expects($this->once())->method('sendRequest')->willReturn($this->mockResponse);

        $this->streamWriter->deleteStream('test-stream', StreamDeletion::SOFT);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function delete_stream_with_hard_mode_adds_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(204);
        $this->mockHttpClient->expects($this->once())->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->hasHeader('Kurrent-HardDelete')
                       && 'true' === $request->getHeaderLine('Kurrent-HardDelete');
            }))
            ->willReturn($this->mockResponse)
        ;

        $this->streamWriter->deleteStream('test-stream', StreamDeletion::HARD);

        $this->addToAssertionCount(1);
    }
}
