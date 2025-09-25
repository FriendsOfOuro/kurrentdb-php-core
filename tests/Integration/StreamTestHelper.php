<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Integration;

use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;

trait StreamTestHelper
{
    /**
     * Prepares a test stream with specified number of events.
     *
     * @throws WrongExpectedVersionException
     */
    protected function prepareTestStream(int $length = 1, array $metadata = []): string
    {
        $streamName = uniqid();
        $events = [];

        for ($i = 0; $i < $length; ++$i) {
            $events[] = WritableEvent::newInstance('Foo_Event', ['foo_data_key' => 'bar'], $metadata);
        }

        $collection = new WritableEventCollection($events);
        $this->es->writeToStream($streamName, $collection);

        return $streamName;
    }
}
