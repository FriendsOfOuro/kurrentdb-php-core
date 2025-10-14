<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use FriendsOfOuro\Http\Batch\ClientInterface;
use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\EventStore;
use KurrentDB\EventStoreFactory;
use KurrentDB\Exception\ConnectionFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class EventStoreFactoryTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private HttpFactory $httpFactory;
    private EventStoreFactory $factory;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->httpFactory = new HttpFactory();
        $this->factory = new EventStoreFactory($this->httpFactory, $this->httpFactory, $this->mockHttpClient);
    }

    /**
     * @throws MockException
     */
    #[Test]
    public function create_returns_event_store_when_connection_succeeds(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockHttpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $eventStore = $this->factory->create();

        $this->assertInstanceOf(EventStore::class, $eventStore);
    }

    /**
     * @throws MockException
     */
    #[Test]
    public function create_throws_connection_failed_exception_when_connection_fails(): void
    {
        $exception = new class('Connection failed', 0) extends \Exception implements ClientExceptionInterface {};

        $this->mockHttpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->expectException(ConnectionFailedException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->factory->create();
    }
}
