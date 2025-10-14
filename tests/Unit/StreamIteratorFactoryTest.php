<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit;

use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\StreamFeed\EntryEmbedMode;
use KurrentDB\StreamFeed\LinkRelation;
use KurrentDB\StreamFeed\StreamFeed;
use KurrentDB\StreamFeed\StreamFeedIterator;
use KurrentDB\StreamIteratorFactory;
use KurrentDB\StreamReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

class StreamIteratorFactoryTest extends TestCase
{
    private StreamReaderInterface&MockObject $mockStreamReader;
    private StreamIteratorFactory $streamIteratorFactory;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->mockStreamReader = $this->createMock(StreamReaderInterface::class);
        $this->streamIteratorFactory = new StreamIteratorFactory($this->mockStreamReader);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function forward_stream_feed_iterator_creates_forward_iterator_with_correct_configuration(): void
    {
        $iterator = $this->streamIteratorFactory->forwardStreamFeedIterator('test-stream');

        $this->assertIteratorHasConfiguration($iterator, 'test-stream', LinkRelation::LAST, LinkRelation::PREVIOUS);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function backward_stream_feed_iterator_creates_backward_iterator_with_correct_configuration(): void
    {
        $iterator = $this->streamIteratorFactory->backwardStreamFeedIterator('test-stream');

        $this->assertIteratorHasConfiguration($iterator, 'test-stream', LinkRelation::FIRST, LinkRelation::NEXT);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function forward_iterator_with_page_limit_passes_limit(): void
    {
        $iterator = $this->streamIteratorFactory->forwardStreamFeedIterator('test-stream', 10);

        $this->assertIteratorHasConfiguration($iterator, 'test-stream', LinkRelation::LAST, LinkRelation::PREVIOUS);
        $this->assertIteratorHasPageLimit($iterator, 9); // pageLimit - 1 for pagesLeft
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function backward_iterator_with_page_limit_passes_limit(): void
    {
        $iterator = $this->streamIteratorFactory->backwardStreamFeedIterator('test-stream', 5);

        $this->assertIteratorHasConfiguration($iterator, 'test-stream', LinkRelation::FIRST, LinkRelation::NEXT);
        $this->assertIteratorHasPageLimit($iterator, 4); // pageLimit - 1 for pagesLeft
    }

    /**
     * @throws MockException
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function forward_iterator_uses_injected_stream_reader(): void
    {
        $streamFeed = new StreamFeed(
            [],
            [],
            ['entries' => [], 'links' => []],
            EntryEmbedMode::NONE,
        );

        $this->mockStreamReader->expects($this->once())
            ->method('openStreamFeed')
            ->with('test-stream')
            ->willReturn($streamFeed)
        ;

        $iterator = $this->streamIteratorFactory->forwardStreamFeedIterator('test-stream');

        // Trigger rewind to verify stream reader is used
        $iterator->rewind();
        $this->assertFalse($iterator->valid()); // Empty stream
    }

    /**
     * @throws MockException
     * @throws BadRequestException
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    #[Test]
    public function backward_iterator_uses_injected_stream_reader(): void
    {
        $streamFeed = new StreamFeed(
            [],
            [],
            ['entries' => [], 'links' => []],
            EntryEmbedMode::NONE,
        );

        $this->mockStreamReader->expects($this->once())
            ->method('openStreamFeed')
            ->with('test-stream')
            ->willReturn($streamFeed)
        ;

        $iterator = $this->streamIteratorFactory->backwardStreamFeedIterator('test-stream');

        // Trigger rewind to verify stream reader is used
        $iterator->rewind();
        $this->assertFalse($iterator->valid()); // Empty stream
    }

    /**
     * @throws \ReflectionException
     */
    private function assertIteratorHasConfiguration(
        StreamFeedIterator $iterator,
        string $expectedStreamName,
        LinkRelation $expectedStartingRelation,
        LinkRelation $expectedNavigationRelation,
    ): void {
        $reflection = new \ReflectionClass($iterator);

        $streamNameProperty = $reflection->getProperty('streamName');
        $startingRelationProperty = $reflection->getProperty('startingRelation');
        $navigationRelationProperty = $reflection->getProperty('navigationRelation');

        $this->assertEquals($expectedStreamName, $streamNameProperty->getValue($iterator));
        $this->assertEquals($expectedStartingRelation, $startingRelationProperty->getValue($iterator));
        $this->assertEquals($expectedNavigationRelation, $navigationRelationProperty->getValue($iterator));
    }

    /**
     * @throws \ReflectionException
     */
    private function assertIteratorHasPageLimit(StreamFeedIterator $iterator, int $expectedPagesLeft): void
    {
        $reflection = new \ReflectionClass($iterator);
        $pagesLeftProperty = $reflection->getProperty('pagesLeft');

        $this->assertEquals($expectedPagesLeft, $pagesLeftProperty->getValue($iterator));
    }
}
