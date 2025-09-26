<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\Http;

use GuzzleHttp\Psr7\Uri;
use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\UnauthorizedException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Http\HttpErrorHandler;
use KurrentDB\Http\ResponseCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpErrorHandlerTest extends TestCase
{
    private HttpErrorHandler $errorHandler;
    private UriInterface $uri;

    protected function setUp(): void
    {
        $this->errorHandler = new HttpErrorHandler();
        $this->uri = new Uri('http://admin:changeit@127.0.0.1:2113/streams/test-stream');
    }

    /**
     * @param class-string<\Exception> $expectedException
     *
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     */
    #[Test]
    #[DataProvider('statusCodeProvider')]
    public function handle_status_code_throws_correct_exception(int $statusCode, string $expectedException, ?string $reasonPhrase = null): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn($reasonPhrase ?? '');

        $this->expectException($expectedException);
        $this->errorHandler->handleStatusCode($this->uri, $response);
    }

    #[Test]
    public function handle_status_code_throws_bad_request_for_400_with_other_reason(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);
        $response->method('getReasonPhrase')->willReturn('Bad Request');

        $this->expectException(BadRequestException::class);
        $this->errorHandler->handleStatusCode($this->uri, $response);
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     * @throws BadRequestException
     */
    #[Test]
    public function handle_status_code_does_nothing_for_success_codes(): void
    {
        $this->expectNotToPerformAssertions();

        $response200 = $this->createMock(ResponseInterface::class);
        $response200->method('getStatusCode')->willReturn(200);
        $response200->method('getReasonPhrase')->willReturn('OK');

        $response201 = $this->createMock(ResponseInterface::class);
        $response201->method('getStatusCode')->willReturn(201);
        $response201->method('getReasonPhrase')->willReturn('Created');

        $response204 = $this->createMock(ResponseInterface::class);
        $response204->method('getStatusCode')->willReturn(204);
        $response204->method('getReasonPhrase')->willReturn('No Content');

        // Should not throw any exception for success codes
        $this->errorHandler->handleStatusCode($this->uri, $response200);
        $this->errorHandler->handleStatusCode($this->uri, $response201);
        $this->errorHandler->handleStatusCode($this->uri, $response204);
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamGoneException
     * @throws MockException
     */
    #[Test]
    public function handle_exception_with_response_delegates_to_handle_status_code(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(ResponseCode::HTTP_NOT_FOUND);

        // Create concrete exception with response
        $exception = new class($response) extends \Exception implements ClientExceptionInterface {
            public function __construct(private readonly ResponseInterface $response)
            {
                parent::__construct('Test exception');
            }

            public function getResponse(): ResponseInterface
            {
                return $this->response;
            }
        };

        $this->expectException(StreamNotFoundException::class);
        $this->errorHandler->handleException($this->uri, $exception);
    }

    #[Test]
    public function handle_exception_without_response_throws_connection_failed(): void
    {
        // Exception without getResponse method - uses the one created in another test
        $exception = new class('Network error', 500) extends \Exception implements ClientExceptionInterface {
        };

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Network error');
        $this->expectExceptionCode(500);

        $this->errorHandler->handleException($this->uri, $exception);
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     */
    #[Test]
    public function handle_exception_with_null_response_throws_connection_failed(): void
    {
        $exception = new class('Request failed', 42) extends \Exception implements ClientExceptionInterface {
            public function getResponse(): null
            {
                return null;
            }
        };

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Request failed');
        $this->expectExceptionCode(42);

        $this->errorHandler->handleException($this->uri, $exception);
    }

    /**
     * @throws WrongExpectedVersionException
     * @throws StreamNotFoundException
     * @throws StreamGoneException
     */
    #[Test]
    public function handle_exception_without_get_response_method_throws_connection_failed(): void
    {
        // Create a mock that doesn't have getResponse method
        $exception = new class('Custom exception', 123) extends \Exception implements ClientExceptionInterface {
        };

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Custom exception');
        $this->expectExceptionCode(123);

        $this->errorHandler->handleException($this->uri, $exception);
    }

    /**
     * Data provider for status codes and expected exceptions.
     *
     * @return array<string, array{int, class-string<\Exception>}>
     */
    public static function statusCodeProvider(): array
    {
        return [
            'Bad Request (Version Conflict)' => [ResponseCode::HTTP_BAD_REQUEST, WrongExpectedVersionException::class, 'Wrong expected EventNumber'],
            'Unauthorized' => [ResponseCode::HTTP_UNAUTHORIZED, UnauthorizedException::class],
            'Not Found' => [ResponseCode::HTTP_NOT_FOUND, StreamNotFoundException::class],
            'Gone' => [ResponseCode::HTTP_GONE, StreamGoneException::class],
            'Internal Server Error' => [ResponseCode::HTTP_INTERNAL_SERVER_ERROR, ConnectionFailedException::class],
            'Bad Gateway' => [ResponseCode::HTTP_BAD_GATEWAY, ConnectionFailedException::class],
            'Service Unavailable' => [ResponseCode::HTTP_SERVICE_UNAVAILABLE, ConnectionFailedException::class],
            'Gateway Timeout' => [ResponseCode::HTTP_GATEWAY_TIMEOUT, ConnectionFailedException::class],
            'Too Many Requests' => [ResponseCode::HTTP_TOO_MANY_REQUESTS, ConnectionFailedException::class],
        ];
    }
}
