<?php

require __DIR__ . '/../vendor/autoload.php';

use EventStore\EventStore;
use EventStore\Http\GuzzleHttpClient;
use EventStore\WritableEvent;
use EventStore\WritableEventCollection;

function prepare_test_stream(EventStore $es, int $length = 1, array $metadata = []): string
{
    $streamName = uniqid();
    $events = [];

    for ($i = 0; $i < $length; ++$i) {
        $events[] = WritableEvent::newInstance('Foo', ['foo' => 'bar'], $metadata);
    }

    $collection = new WritableEventCollection($events);
    $es->writeToStream($streamName, $collection);

    return $streamName;
}

$es = new EventStore('http://127.0.0.1:2113', GuzzleHttpClient::withFilesystemCache('/tmp/es-client'));

$streamName = prepare_test_stream($es, $count = 1000);

$start = microtime(true);

$stream = $es->forwardStreamFeedIterator($streamName);
foreach ($stream as $event) {
    // do nothing on purpose
}

$end = microtime(true);

printf('Reading %d events took %f seconds%s', $count, $end - $start, PHP_EOL);

$start = microtime(true);

$stream = $es->forwardStreamFeedIterator($streamName);
foreach ($stream as $event) {
    // do nothing on purpose
}

$end = microtime(true);

printf('Reading the same %d events again took %f seconds%s', $count, $end - $start, PHP_EOL);
