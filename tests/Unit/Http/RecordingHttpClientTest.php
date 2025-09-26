<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\Http;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;
use KurrentDB\Http\RecordingHttpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(RecordingHttpClient::class)]
final class RecordingHttpClientTest extends TestCase
{
    private ClientInterface&MockObject $innerClient;
    private RecordingHttpClient $recordingClient;
    private RequestInterface&MockObject $request;
    private ResponseInterface&MockObject $response;

    protected function setUp(): void
    {
        $this->innerClient = $this->createMock(ClientInterface::class);
        $this->recordingClient = new RecordingHttpClient($this->innerClient);
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function test_it_records_successful_request_and_response(): void
    {
        $this->innerClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->request)
            ->willReturn($this->response)
        ;

        $result = $this->recordingClient->sendRequest($this->request);

        $this->assertSame($this->response, $result);
        $this->assertSame([$this->request], $this->recordingClient->getRecordedRequests());
        $this->assertSame([$this->response], $this->recordingClient->getRecordedResponses());
        $this->assertEmpty($this->recordingClient->getRecordedExceptions());
        $this->assertSame($this->request, $this->recordingClient->getLastRequest());
        $this->assertSame($this->response, $this->recordingClient->getLastResponse());
        $this->assertNull($this->recordingClient->getLastException());
        $this->assertEquals(1, $this->recordingClient->getRequestCount());
        $this->assertEquals(1, $this->recordingClient->getResponseCount());
        $this->assertEquals(0, $this->recordingClient->getExceptionCount());
        $this->assertFalse($this->recordingClient->hasExceptions());
    }

    public function test_it_records_request_and_exception(): void
    {
        $exception = $this->createMock(ClientExceptionInterface::class);

        $this->innerClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->request)
            ->willThrowException($exception)
        ;

        $this->expectException(ClientExceptionInterface::class);

        try {
            $this->recordingClient->sendRequest($this->request);
        } finally {
            $this->assertSame([$this->request], $this->recordingClient->getRecordedRequests());
            $this->assertEmpty($this->recordingClient->getRecordedResponses());
            $this->assertSame([$exception], $this->recordingClient->getRecordedExceptions());
            $this->assertSame($this->request, $this->recordingClient->getLastRequest());
            $this->assertNull($this->recordingClient->getLastResponse());
            $this->assertSame($exception, $this->recordingClient->getLastException());
            $this->assertEquals(1, $this->recordingClient->getRequestCount());
            $this->assertEquals(0, $this->recordingClient->getResponseCount());
            $this->assertEquals(1, $this->recordingClient->getExceptionCount());
            $this->assertTrue($this->recordingClient->hasExceptions());
        }
    }

    public function test_it_records_multiple_interactions(): void
    {
        $request2 = $this->createMock(RequestInterface::class);
        $response2 = $this->createMock(ResponseInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);

        // First successful request
        $this->innerClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($request2, $response2, $exception) {
                if ($request === $this->request) {
                    return $this->response;
                }
                if ($request === $request2) {
                    throw $exception;
                }

                return $response2;
            })
        ;

        // Request 1: Success
        $this->recordingClient->sendRequest($this->request);

        // Request 2: Exception
        try {
            $this->recordingClient->sendRequest($request2);
        } catch (ClientExceptionInterface) {
            // Expected
        }

        // Request 3: Success
        $request3 = $this->createMock(RequestInterface::class);
        $this->recordingClient->sendRequest($request3);

        $this->assertEquals(3, $this->recordingClient->getRequestCount());
        $this->assertEquals(2, $this->recordingClient->getResponseCount());
        $this->assertEquals(1, $this->recordingClient->getExceptionCount());

        $interactions = $this->recordingClient->getInteractions();
        $this->assertCount(3, $interactions);

        // First interaction: success
        $this->assertSame($this->request, $interactions[0]['request']);
        $this->assertArrayHasKey('response', $interactions[0]);
        $this->assertSame($this->response, $interactions[0]['response'] ?? null);
        $this->assertArrayNotHasKey('exception', $interactions[0]);

        // Second interaction: exception
        $this->assertSame($request2, $interactions[1]['request']);
        $this->assertArrayHasKey('exception', $interactions[1]);
        $this->assertSame($exception, $interactions[1]['exception'] ?? null);
        $this->assertArrayNotHasKey('response', $interactions[1]);

        // Third interaction: success
        $this->assertSame($request3, $interactions[2]['request']);
        $this->assertArrayHasKey('response', $interactions[2]);
        $this->assertSame($response2, $interactions[2]['response'] ?? null);
        $this->assertArrayNotHasKey('exception', $interactions[2]);
    }

    public function test_it_filters_successful_and_failed_requests(): void
    {
        $request2 = $this->createMock(RequestInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);

        $this->innerClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($exception) {
                if ($request === $this->request) {
                    return $this->response;
                }
                throw $exception;
            })
        ;

        $this->recordingClient->sendRequest($this->request);

        try {
            $this->recordingClient->sendRequest($request2);
        } catch (ClientExceptionInterface) {
            // Expected
        }

        $this->assertSame([$this->request], $this->recordingClient->getSuccessfulRequests());
        $this->assertSame([$request2], $this->recordingClient->getFailedRequests());
    }

    public function test_it_can_clear_recordings(): void
    {
        $this->innerClient
            ->method('sendRequest')
            ->willReturn($this->response)
        ;

        $this->recordingClient->sendRequest($this->request);
        $this->assertEquals(1, $this->recordingClient->getRequestCount());

        $this->recordingClient->clearRecordings();

        $this->assertEmpty($this->recordingClient->getRecordedRequests());
        $this->assertEmpty($this->recordingClient->getRecordedResponses());
        $this->assertEmpty($this->recordingClient->getRecordedExceptions());
        $this->assertEmpty($this->recordingClient->getInteractions());
        $this->assertEquals(0, $this->recordingClient->getRequestCount());
        $this->assertNull($this->recordingClient->getLastRequest());
    }

    public function test_it_delegates_batch_requests(): void
    {
        $batch = $this->createMock(ResponseBatchInterface::class);
        $requests = [$this->request];

        $this->innerClient
            ->expects($this->once())
            ->method('sendRequestBatch')
            ->with($requests)
            ->willReturn($batch)
        ;

        $result = $this->recordingClient->sendRequestBatch($requests);

        $this->assertSame($batch, $result);
    }

    public function test_empty_state_returns_null_and_zero_counts(): void
    {
        $this->assertNull($this->recordingClient->getLastRequest());
        $this->assertNull($this->recordingClient->getLastResponse());
        $this->assertNull($this->recordingClient->getLastException());
        $this->assertNull($this->recordingClient->getLastInteraction());
        $this->assertEquals(0, $this->recordingClient->getRequestCount());
        $this->assertEquals(0, $this->recordingClient->getResponseCount());
        $this->assertEquals(0, $this->recordingClient->getExceptionCount());
        $this->assertFalse($this->recordingClient->hasExceptions());
        $this->assertEmpty($this->recordingClient->getSuccessfulRequests());
        $this->assertEmpty($this->recordingClient->getFailedRequests());
    }
}
