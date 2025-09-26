<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\Http;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\Exception\ConnectionFailedException;
use KurrentDB\Http\ConnectionChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class ConnectionCheckerTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private ConnectionChecker $connectionChecker;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $httpFactory = new HttpFactory();
        $this->connectionChecker = new ConnectionChecker($httpFactory, $this->mockHttpClient);
    }

    #[Test]
    public function check_connection_succeeds_with_successful_response(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockHttpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $this->connectionChecker->checkConnection();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function check_connection_throws_connection_failed_exception_on_http_error(): void
    {
        $exception = new class('Connection refused', 0) extends \Exception implements ClientExceptionInterface {};

        $this->mockHttpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->connectionChecker->checkConnection();
    }

    #[Test]
    public function check_connection_throws_connection_failed_exception_on_general_error(): void
    {
        $exception = new \RuntimeException('Network error', 500);

        $this->mockHttpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Network error');
        $this->expectExceptionCode(500);

        $this->connectionChecker->checkConnection();
    }
}
