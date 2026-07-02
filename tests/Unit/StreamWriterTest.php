<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\NoExtractableEventVersionException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\ExpectedVersion;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamDeletion;
use KurrentDB\StreamWriter;
use KurrentDB\StreamWriteResult;
use KurrentDB\Tests\SerializerFactory;
use KurrentDB\ValueObjects\Identity\UUID;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class StreamWriterTest extends TestCase
{
    private ClientInterface&Stub $mockHttpClient;
    private ResponseInterface&Stub $mockResponse;
    private StreamInterface&Stub $mockBody;
    private HttpFactory $httpFactory;
    private HttpErrorHandler $httpErrorHandler;
    private SerializerInterface $serializer;
    private StreamWriter $streamWriter;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createStub(ClientInterface::class);
        $this->mockResponse = $this->createStub(ResponseInterface::class);
        $this->mockBody = $this->createStub(StreamInterface::class);

        $this->mockResponse->method('getBody')->willReturn($this->mockBody);

        $this->httpFactory = new HttpFactory();
        $this->httpErrorHandler = new HttpErrorHandler();
        $this->serializer = SerializerFactory::create($this->httpFactory);
        $this->streamWriter = $this->makeStreamWriter($this->mockHttpClient);
    }

    private function makeStreamWriter(ClientInterface $client): StreamWriter
    {
        return new StreamWriter(
            $this->httpFactory,
            $this->httpFactory,
            $client,
            $this->httpErrorHandler,
            $this->serializer
        );
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws SerializerExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function write_to_stream_with_single_event_returns_write_result(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->willReturn(['http://example.com/streams/test-stream/0']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);
        $result = $this->streamWriter->writeToStream('test-stream', WritableEventCollection::of($event));

        $this->assertEquals(0, $result->version);
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws SerializerExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function write_to_stream_with_event_collection_returns_write_result(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->willReturn(['http://example.com/streams/test-stream/1']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $events = WritableEventCollection::of(
            new WritableEvent(new UUID(), 'TestEvent1', ['test' => 'data1']),
            new WritableEvent(new UUID(), 'TestEvent2', ['test' => 'data2'])
        );

        $result = $this->streamWriter->writeToStream('test-stream', $events, ExpectedVersion::ANY);

        $this->assertInstanceOf(StreamWriteResult::class, $result);
        $this->assertEquals(1, $result->version);
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws SerializerExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function write_to_stream_throws_exception_when_no_location_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->willReturn([]);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);

        $this->expectException(NoExtractableEventVersionException::class);

        $this->streamWriter->writeToStream('test-stream', WritableEventCollection::of($event));
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws SerializerExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function write_to_stream_throws_exception_when_malformed_location_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->willReturn(['http://example.com/invalid/path']);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);

        $this->expectException(NoExtractableEventVersionException::class);

        $this->streamWriter->writeToStream('test-stream', WritableEventCollection::of($event));
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function delete_stream_sends_delete_request(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(204);

        $mockHttpClient = $this->createMock(ClientInterface::class);
        $mockHttpClient->expects($this->once())->method('sendRequest')->willReturn($this->mockResponse);

        $this->makeStreamWriter($mockHttpClient)->deleteStream('test-stream', StreamDeletion::SOFT);
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function delete_stream_with_hard_mode_adds_header(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(204);

        $mockHttpClient = $this->createMock(ClientInterface::class);
        $mockHttpClient->expects($this->once())->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->hasHeader('Kurrent-HardDelete')
                       && 'true' === $request->getHeaderLine('Kurrent-HardDelete');
            }))
            ->willReturn($this->mockResponse)
        ;

        $this->makeStreamWriter($mockHttpClient)->deleteStream('test-stream', StreamDeletion::HARD);
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws SerializerExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function stream_name_is_url_encoded_in_request_uri(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(201);
        $this->mockResponse->method('getHeader')->willReturn(['http://example.com/streams/x/0']);

        $mockHttpClient = $this->createMock(ClientInterface::class);
        $mockHttpClient->expects($this->once())->method('sendRequest')
            ->with($this->callback(function ($request): bool {
                $uri = $request->getUri();

                // "?two" must stay part of the stream name, not become a query string
                return '/streams/name%3Ftwo' === $uri->getPath() && '' === $uri->getQuery();
            }))
            ->willReturn($this->mockResponse)
        ;

        $event = new WritableEvent(new UUID(), 'TestEvent', ['test' => 'data']);
        $this->makeStreamWriter($mockHttpClient)->writeToStream('name?two', WritableEventCollection::of($event));
    }

    /**
     * @throws MockException
     * @throws ClientExceptionInterface
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function delete_stream_throws_stream_not_found_exception_on_404(): void
    {
        $this->mockResponse->method('getStatusCode')->willReturn(404);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $this->expectException(StreamNotFoundException::class);

        $this->streamWriter->deleteStream('nonexistent-stream', StreamDeletion::SOFT);
    }
}
