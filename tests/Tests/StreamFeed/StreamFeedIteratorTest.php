<?php
namespace EventStore\Tests\StreamFeed;

use EventStore\EventStore;
use EventStore\Http\GuzzleHttpClient;
use EventStore\StreamFeed\Entry;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\StreamFeedIterator;
use EventStore\WritableEvent;
use EventStore\WritableEventCollection;
use PHPUnit\Framework\TestCase;

class StreamFeedIteratorTest extends TestCase
{
    /**
     * @var EventStore
     */
    private $es;

    protected function setUp(): void
    {
        $httpClient = new GuzzleHttpClient();
        $this->es = new EventStore('http://admin:changeit@127.0.0.1:2113', $httpClient);
    }

    /**
     * @test
     */
    public function it_should_iterate_single_event_asc()
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

    /**
     * @test
     */
    public function it_should_iterate_single_event_desc()
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

    /**
     * @test
     */
    public function it_should_iterate_the_second_page()
    {
        $streamLength = 21;
        $streamName = $this->prepareTestStream($streamLength);

        $iterator = StreamFeedIterator::forward($this->es, $streamName);

        $events = iterator_to_array($iterator);

        $this->assertCount($streamLength, $events);
    }

    /**
     * @test
     */
    public function it_should_be_sorted_asc()
    {
        $streamName = $this->prepareTestStream(21);

        $iterator = StreamFeedIterator::forward($this->es, $streamName);

        $this->assertEventSorted(iterator_to_array($iterator));
    }

    /**
     * @test
     */
    public function it_should_be_sorted_desc()
    {
        $streamName = $this->prepareTestStream(21);

        $iterator = StreamFeedIterator::backward($this->es, $streamName);

        $this->assertEventSorted(iterator_to_array($iterator), -1);
    }

    /**
     * @test
     */
    public function it_should_optimize_http_call_on_rewind()
    {
        $streamName = $this->prepareTestStream(1);

        $iterator = StreamFeedIterator::backward($this->es, $streamName);

        $iterator->rewind();
        $response1 = $this->es->getLastResponse();

        $iterator->rewind();
        $response2 = $this->es->getLastResponse();

        $this->assertSame($response1, $response2);
    }

    /**
     * @param int   $length
     * @param array $metadata
     *
     * @return string
     */
    private function prepareTestStream($length = 1, $metadata = [])
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

    private function assertEventSorted(array $events, $sign = 1)
    {
        $unsorted = $events;

        uksort(
            $events,
            function ($a, $b) use ($sign) {
                list($ida) = explode('@', $a);
                list($idb) = explode('@', $b);

                return $sign * ($ida - $idb);
            }
        );

        $this->assertSame(
            $events,
            $unsorted
        );
    }
}
