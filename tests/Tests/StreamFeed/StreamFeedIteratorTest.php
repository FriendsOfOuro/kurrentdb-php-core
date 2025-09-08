<?php

namespace EventStore\Tests\StreamFeed;

use EventStore\StreamFeed\Entry;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\StreamFeedIterator;
use EventStore\Tests\TestCase;
use EventStore\WritableEvent;
use EventStore\WritableEventCollection;
use PHPUnit\Framework\Attributes\Test;

class StreamFeedIteratorTest extends TestCase
{
    #[Test]
    public function it_should_iterate_single_event_asc(): void
    {
        $streamName = uniqid();

        $event = WritableEvent::newInstance('SomethingHappened', ['foo' => 'bar']);
        $this->es->writeToStream($streamName, $event);

        $iterator = StreamFeedIterator::forward($this->es, $streamName);

        $events = iterator_to_array($iterator);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(Event::class, $events['0@' . $streamName]->getEvent());
        $this->assertInstanceOf(Entry::class, $events['0@' . $streamName]->getEntry());
    }

    #[Test]
    public function it_should_iterate_single_event_desc(): void
    {
        $streamName = uniqid();

        $event = WritableEvent::newInstance('SomethingHappened', ['foo' => 'bar']);
        $this->es->writeToStream($streamName, $event);

        $iterator = StreamFeedIterator::backward($this->es, $streamName);

        $events = iterator_to_array($iterator);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(Event::class, $events['0@' . $streamName]->getEvent());
        $this->assertInstanceOf(Entry::class, $events['0@' . $streamName]->getEntry());
    }

    #[Test]
    public function it_should_iterate_the_second_page(): void
    {
        $streamLength = 21;
        $streamName = $this->prepareTestStream($streamLength);

        $iterator = StreamFeedIterator::forward($this->es, $streamName);

        $events = iterator_to_array($iterator);

        $this->assertCount($streamLength, $events);
    }

    #[Test]
    public function it_should_be_sorted_asc(): void
    {
        $streamName = $this->prepareTestStream(21);

        $iterator = StreamFeedIterator::forward($this->es, $streamName);

        $this->assertEventSorted(iterator_to_array($iterator));
    }

    #[Test]
    public function it_should_be_sorted_desc(): void
    {
        $streamName = $this->prepareTestStream(21);

        $iterator = StreamFeedIterator::backward($this->es, $streamName);

        $this->assertEventSorted(iterator_to_array($iterator), -1);
    }

    #[Test]
    public function it_should_optimize_http_call_on_rewind(): void
    {
        $streamName = $this->prepareTestStream(1);

        $iterator = StreamFeedIterator::backward($this->es, $streamName);

        $iterator->rewind();
        $response1 = $this->es->getLastResponse();

        $iterator->rewind();
        $response2 = $this->es->getLastResponse();

        $this->assertSame($response1, $response2);
    }

    private function prepareTestStream(int $length = 1, array $metadata = []): string
    {
        $streamName = uniqid();
        $events = [];

        for ($i = 0; $i < $length; ++$i) {
            $events[] = WritableEvent::newInstance('Foo', ['foo' => 'bar'], $metadata);
        }

        $collection = new WritableEventCollection($events);
        $this->es->writeToStream($streamName, $collection);

        return $streamName;
    }

    private function assertEventSorted(array $events, int $sign = 1): void
    {
        $unsorted = $events;

        uksort(
            $events,
            function ($a, $b) use ($sign): int|float {
                [$ida] = explode('@', $a);
                [$idb] = explode('@', $b);

                return $sign * ($ida - $idb);
            }
        );

        $this->assertSame(
            $events,
            $unsorted
        );
    }
}
