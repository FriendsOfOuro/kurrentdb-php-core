# KurrentDB PHP Client

[![PHP 8](https://github.com/FriendsOfOuro/kurrentdb-php-core/actions/workflows/php_8.yml/badge.svg)](https://github.com/FriendsOfOuro/kurrentdb-php-core/actions/workflows/php_8.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/friendsofouro/kurrentdb-core.svg)](https://packagist.org/packages/friendsofouro/kurrentdb-core)
[![License](https://img.shields.io/packagist/l/friendsofouro/kurrentdb-core.svg)](https://packagist.org/packages/friendsofouro/kurrentdb-core)

A modern PHP client library for [KurrentDB](https://kurrent.io/) (formerly EventStoreDB) HTTP API, designed for event sourcing applications.

> **Note:** This library uses the HTTP API. For TCP integration, see [prooph/event-store-client](https://github.com/prooph/event-store-client).

## Features

- ✅ Support for KurrentDB HTTP API
- ✅ Event stream management (read, write, delete)
- ✅ Optimistic concurrency control
- ✅ Stream iteration (forward and backward)
- ✅ Batch operations for performance
- ✅ Built-in HTTP caching support
- ✅ PSR-7 and PSR-18 compliant
- ✅ Type-safe with PHP 8.4 features
- ✅ Comprehensive error handling

## Requirements

- PHP 8.4 or higher
- KurrentDB server (HTTP API enabled)

## Installation

### Via Composer

```bash
composer require friendsofouro/kurrentdb-core
```

### Via Metapackage (Recommended)

For the complete package with additional integrations:

```bash
composer require friendsofouro/kurrentdb
```

## Quick Start

### Basic Setup

```php
use KurrentDB\EventStore;
use KurrentDB\Http\GuzzleHttpClient;

// Create HTTP client and connect to KurrentDB
$httpClient = new GuzzleHttpClient();
$eventStore = new EventStore('http://admin:changeit@127.0.0.1:2113', $httpClient);
```

### Writing Events

```php
use KurrentDB\WritableEvent;
use KurrentDB\WritableEventCollection;

// Write a single event
$event = WritableEvent::newInstance(
    'UserRegistered',
    ['userId' => '123', 'email' => 'user@example.com'],
    ['timestamp' => time()] // optional metadata
);

$version = $eventStore->writeToStream('user-123', $event);

// Write multiple events atomically
$events = new WritableEventCollection([
    WritableEvent::newInstance('OrderPlaced', ['orderId' => '456']),
    WritableEvent::newInstance('PaymentProcessed', ['amount' => 99.99])
]);

$eventStore->writeToStream('order-456', $events);
```

### Reading Events

```php
use KurrentDB\StreamFeed\EntryEmbedMode;

$feed = $eventStore->openStreamFeed('user-123');

// Get entries and read events
foreach ($feed->getEntries() as $entry) {
    $event = $eventStore->readEvent($entry->getEventUrl());
    echo sprintf("Event: %s, Version: %d\n", 
        $event->getType(), 
        $event->getVersion()
    );
}

// Read with embedded event data for better performance
$feed = $eventStore->openStreamFeed('user-123', EntryEmbedMode::BODY);
```

### Stream Navigation

```php
use KurrentDB\StreamFeed\LinkRelation;

// Navigate through pages
$feed = $eventStore->openStreamFeed('large-stream');
$nextPage = $eventStore->navigateStreamFeed($feed, LinkRelation::NEXT);

// Use iterators for convenient traversal
$iterator = $eventStore->forwardStreamFeedIterator('user-123');
foreach ($iterator as $entryWithEvent) {
    $event = $entryWithEvent->getEvent();
    // Process event...
}

// Backward iteration
$reverseIterator = $eventStore->backwardStreamFeedIterator('user-123');
```

### Optimistic Concurrency Control

```php
use KurrentDB\ExpectedVersion;

// Write with expected version
$eventStore->writeToStream(
    'user-123', 
    $event, 
    ExpectedVersion::exact(5) // Expects stream to be at version 5
);

// Special version expectations
$eventStore->writeToStream('new-stream', $event, ExpectedVersion::NO_STREAM);
$eventStore->writeToStream('any-stream', $event, ExpectedVersion::ANY);
```

### Stream Management

```php
use KurrentDB\StreamDeletion;

// Soft delete (can be recreated)
$eventStore->deleteStream('old-stream', StreamDeletion::SOFT);

// Hard delete (permanent, will be 410 Gone)
$eventStore->deleteStream('obsolete-stream', StreamDeletion::HARD);
```

## Advanced Usage

### HTTP Caching

Improve performance with built-in caching:

```php
// Filesystem cache
$httpClient = GuzzleHttpClient::withFilesystemCache('/tmp/kurrentdb-cache');

// APCu cache (in-memory)
$httpClient = GuzzleHttpClient::withApcuCache();

// Custom PSR-6 cache
use Symfony\Component\Cache\Adapter\RedisAdapter;
$cacheAdapter = new RedisAdapter($redisClient);
$httpClient = GuzzleHttpClient::withPsr6Cache($cacheAdapter);

$eventStore = new EventStore($url, $httpClient);
```

### Batch Operations

Read multiple events efficiently:

```php
// Collect event URLs
$eventUrls = [];
foreach ($feed->getEntries() as $entry) {
    $eventUrls[] = $entry->getEventUrl();
}

// Batch read
$events = $eventStore->readEventBatch($eventUrls);
foreach ($events as $event) {
    // Process events...
}
```

### Error Handling

```php
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\Exception\StreamDeletedException;

try {
    $eventStore->writeToStream('user-123', $event, ExpectedVersion::exact(10));
} catch (WrongExpectedVersionException $e) {
    // Handle version conflict
    echo "Version mismatch: " . $e->getMessage();
} catch (StreamNotFoundException $e) {
    // Stream doesn't exist
    echo "Stream not found: " . $e->getMessage();
} catch (StreamDeletedException $e) {
    // Stream was deleted
    echo "Stream deleted: " . $e->getMessage();
}
```

### Custom HTTP Client

You can provide your own HTTP client implementing `HttpClientInterface`:

```php
use KurrentDB\Http\HttpClientInterface;

class MyCustomHttpClient implements HttpClientInterface
{
    public function send(RequestInterface $request): ResponseInterface
    {
        // Custom implementation
    }
    
    public function sendBatch(RequestInterface ...$requests): \Iterator
    {
        // Batch implementation
    }
}

$eventStore = new EventStore($url, new MyCustomHttpClient());
```

## Testing

Run the test suite:

```bash
make test
```

Run with code coverage:

```bash
make coverage
```

## Configuration Examples

### Docker Compose Setup

```yaml
version: '3.8'
services:
  kurrentdb:
    image: ghcr.io/kurrentdb/kurrentdb:latest
    ports:
      - "2113:2113"
    environment:
      - EVENTSTORE_CLUSTER_SIZE=1
      - EVENTSTORE_RUN_PROJECTIONS=All
      - EVENTSTORE_START_STANDARD_PROJECTIONS=true
      - EVENTSTORE_HTTP_PORT=2113
      - EVENTSTORE_INSECURE=true
```

### Production Configuration

```php
use KurrentDB\EventStore;
use KurrentDB\Http\GuzzleHttpClient;
use GuzzleHttp\Client;

// Configure Guzzle with production settings
$guzzle = new Client([
    'timeout' => 10,
    'connect_timeout' => 5,
    'auth' => ['admin', 'your-secure-password'],
    'headers' => [
        'User-Agent' => 'MyApp/1.0'
    ]
]);

// Use caching for better performance
$httpClient = GuzzleHttpClient::withFilesystemCache(
    '/var/cache/kurrentdb',
    $guzzle
);

$eventStore = new EventStore('https://kurrentdb.example.com', $httpClient);
```

## API Reference

### Main Classes

- **`EventStore`** - Main client class for all operations
- **`WritableEvent`** - Represents an event to be written
- **`WritableEventCollection`** - Collection of events for atomic writes
- **`StreamFeed`** - Paginated view of a stream
- **`Event`** - Represents a read event with version and metadata

### Enums

- **`StreamDeletion`** - SOFT or HARD deletion modes
- **`EntryEmbedMode`** - NONE, RICH, or BODY embed modes
- **`LinkRelation`** - FIRST, LAST, NEXT, PREVIOUS, etc.

### Interfaces

- **`EventStoreInterface`** - Main service interface
- **`HttpClientInterface`** - HTTP client abstraction
- **`WritableToStream`** - Objects that can be written to streams

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Disclaimer

This project is not endorsed by Event Store LLP nor Kurrent Inc.

## Support

- [GitHub Issues](https://github.com/FriendsOfOuro/kurrentdb-php-core/issues)
- [Documentation](https://docs.kurrent.io/)
- [KurrentDB Community](https://kurrent.io/community)
