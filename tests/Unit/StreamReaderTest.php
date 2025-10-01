<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\Event;
use KurrentDB\StreamFeed\Link;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamReader;
use KurrentDB\Tests\SerializerFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class StreamReaderTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private ResponseInterface&MockObject $mockResponse;
    private StreamInterface&MockObject $mockBody;
    private StreamReader $streamReader;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockBody = $this->createMock(StreamInterface::class);

        $this->mockResponse->method('getBody')->willReturn($this->mockBody);

        $httpFactory = new HttpFactory();
        $httpErrorHandler = new HttpErrorHandler();
        $serializer = SerializerFactory::create($httpFactory);

        $this->streamReader = new StreamReader(
            $httpFactory,
            $httpFactory,
            $this->mockHttpClient,
            $httpErrorHandler,
            $serializer
        );
    }

    #[Test]
    public function open_stream_feed_returns_stream_feed(): void
    {
        $jsonResponse = [
            'entries' => [],
            'links' => [
                ['relation' => 'self', 'uri' => 'http://example.com/streams/test-stream'],
            ],
        ];

        $this->mockBody->method('__toString')->willReturn(json_encode($jsonResponse));
        $this->mockResponse->method('getStatusCode')->willReturn(200);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $result = $this->streamReader->openStreamFeed('test-stream');

        $this->assertEquals(EntryEmbedMode::NONE, $result->getEntryEmbedMode());
    }

    #[Test]
    public function navigate_stream_feed_returns_null_when_no_link(): void
    {
        $streamFeed = new StreamFeed(
            [],
            [],
            ['entries' => [], 'links' => []],
            EntryEmbedMode::NONE,
        );

        $result = $this->streamReader->navigateStreamFeed($streamFeed, LinkRelation::NEXT);

        $this->assertNull($result);
    }

    #[Test]
    public function navigate_stream_feed_returns_stream_feed_when_link_exists(): void
    {
        $jsonResponse = [
            'entries' => [],
            'links' => [],
        ];

        $this->mockBody->method('__toString')->willReturn(json_encode($jsonResponse));
        $this->mockResponse->method('getStatusCode')->willReturn(200);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $link = new Link(LinkRelation::NEXT, new Uri('http://example.com/streams/test-stream/next'));
        $streamFeed = new StreamFeed(
            [$link],
            [],
            ['entries' => [], 'links' => [['relation' => 'next', 'uri' => 'http://example.com/streams/test-stream/next']]],
            EntryEmbedMode::NONE,
        );

        $result = $this->streamReader->navigateStreamFeed($streamFeed, LinkRelation::NEXT);

        $this->assertInstanceOf(StreamFeed::class, $result);
    }

    #[Test]
    public function read_event_returns_event(): void
    {
        $eventContent = [
            'eventType' => 'TestEvent',
            'eventNumber' => 0,
            'data' => ['test' => 'data'],
            'metadata' => null,
            'eventId' => '12345678-1234-1234-1234-123456789012',
        ];

        $jsonResponse = ['content' => $eventContent];

        $this->mockBody->method('__toString')->willReturn(json_encode($jsonResponse));
        $this->mockResponse->method('getStatusCode')->willReturn(200);
        $this->mockHttpClient->method('sendRequest')->willReturn($this->mockResponse);

        $eventUri = new Uri('http://example.com/streams/test-stream/0');
        $result = $this->streamReader->readEvent($eventUri);

        $this->assertEquals('TestEvent', $result->getType());
        $this->assertEquals(0, $result->getVersion());
    }

    #[Test]
    public function read_event_batch_returns_events_array(): void
    {
        $eventContent = [
            'eventType' => 'TestEvent',
            'eventNumber' => 0,
            'data' => ['test' => 'data'],
            'metadata' => null,
            'eventId' => '12345678-1234-1234-1234-123456789012',
        ];

        $response1 = $this->createMock(ResponseInterface::class);
        $body1 = $this->createMock(StreamInterface::class);
        $body1->method('__toString')->willReturn(json_encode(['content' => $eventContent]));
        $response1->method('getBody')->willReturn($body1);

        $mockBatch = $this->createMock(ResponseBatchInterface::class);
        $mockBatch->method('hasAnyFailures')->willReturn(false);
        $mockBatch->method('getResponses')->willReturn([$response1]);

        $this->mockHttpClient->method('sendRequestBatch')->willReturn($mockBatch);

        $eventUrls = [new Uri('http://example.com/streams/test-stream/0')];
        $result = $this->streamReader->readEventBatch($eventUrls);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(Event::class, $result);
    }
}
