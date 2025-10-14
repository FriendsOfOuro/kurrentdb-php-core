## 🔧 Breaking Changes

- **WritableToStream Interface Removed**: The `WritableToStream` interface has been removed in favor of using `WritableEventCollection` directly
  ```php
  // Before
  public function writeToStream(string $streamName, WritableToStream $events): StreamWriteResult

  // After
  public function writeToStream(string $streamName, WritableEventCollection $events): StreamWriteResult
  ```

- **WritableEventCollection API Changes**: Constructor is now private, use `of()` static method instead
  ```php
  // Before
  $collection = new WritableEventCollection([$event1, $event2]);

  // After
  $collection = WritableEventCollection::of($event1, $event2);
  ```

- **WritableEventCollection Events Access**: Events are now accessed via public property instead of getter method
  ```php
  // Before
  $events = $collection->getEvents();

  // After
  $events = $collection->events;
  ```

- **Factory Classes Removed**: `StreamFeedFactory`, `StreamFeedFactoryInterface`, and `EntryFactory` have been removed
  - Symfony Serializer is now used for deserialization
  - EventStoreFactory handles all serializer configuration internally

- **EventStoreFactory Changes**: Constructor now sets up Symfony Serializer with denormalizers and normalizers
  - Applications using EventStoreFactory don't need changes
  - Custom EventStore instantiation requires Symfony Serializer setup

- **WritableEvent Constructor**: Parameters are now public readonly properties
  ```php
  // Before
  new WritableEvent($uuid, $type, $data, $metadata)

  // After - same signature but properties are now public readonly
  new WritableEvent($uuid, $type, $data, $metadata)
  ```

- **StreamFeed Constructor**: Now requires pre-constructed entries array
  ```php
  // Before
  new StreamFeed($links, $json, $embedMode, $entryFactory)

  // After
  new StreamFeed($links, $entries, $json, $embedMode)
  ```

- **EntryEmbedMode**: No longer nullable, defaults to `EntryEmbedMode::NONE`
  - All methods expecting `?EntryEmbedMode` now expect `EntryEmbedMode` with NONE as default

- **toStreamData() Methods Removed**: Serialization now handled by Symfony Serializer
  - WritableEvent and WritableEventCollection no longer have `toStreamData()` methods
  - Use Symfony Serializer for custom serialization needs

- **Exception Changes**: `InvalidWritableEventObjectException` removed
  - New exceptions: `SerializationException`, `DeserializationException`
  - Better error context with wrapped Symfony Serializer exceptions

- **UUID Constructor**: Now accepts only string argument (breaking for direct instantiation)
  ```php
  // Before
  UUID::fromNative($uuidString)

  // After - improved clarity
  new UUID($uuidString)  // Direct construction now clearer
  UUID::fromNative($uuidString)  // Still available
  ```

## ✨ New Features

- **Symfony Serializer Integration**: Full integration with Symfony Serializer component
  - Automatic normalization/denormalization of events and streams
  - Extensible with custom normalizers/denormalizers
  - Better error handling during serialization/deserialization

- **Custom Denormalizers**: New denormalizer classes for domain objects
  - `StreamFeedDenormalizer` - StreamFeed deserialization
  - `EntryDenormalizer` - Entry deserialization
  - `EventDenormalizer` - Event deserialization
  - `LinkDenormalizer` - Link deserialization
  - `WritableEventNormalizer` - Event normalization for writing

- **Enhanced Error Handling**: New exception types for better error diagnostics
  - `SerializationException` - Thrown when event serialization fails
  - `DeserializationException` - Thrown when response deserialization fails
  - Both exceptions wrap Symfony Serializer exceptions with additional context

- **Improved deleteStream**: Now properly handles and reports HTTP error responses
  - Error responses are processed through HttpErrorHandler
  - Better exception messages for stream deletion failures

## 🛠️ Improvements

- **Type Safety**: Explicit type declarations added to ExpectedVersion constants
  ```php
  public const int ANY = -2;
  public const int NO_STREAM = -1;
  ```

- **PHPDoc Compliance**: Comprehensive PHPDoc improvements for PHPStan level 7
  - Added missing `@throws` tags throughout the codebase
  - Sorted exception tags alphabetically
  - Used `use` clauses instead of FQN in @throws tags
  - Proper MockException aliases in tests

- **Test Organization**: Better test structure and helper classes
  - `SerializerFactory` test helper for consistent serializer setup
  - Improved test readability with clearer method names
  - Removed unnecessary ClientExceptionInterface @throws from tests

- **Batch Event Reading**: Improved batch event reading implementation
  - Better handling of responses that cannot be deserialized
  - Filters out non-event responses gracefully
  - Returns array values without keys

- **Code Quality**: Various code quality improvements
  - Removed unused `responseAsJson()` method from HttpClientTrait
  - Better separation of concerns with Symfony Serializer
  - Improved file organization and naming conventions
  - Enhanced code clarity throughout

- **Development Workflow**: Enhanced development experience
  - Added `make before-push` target for pre-commit checks
  - Runs cs-fixer, tests, and phpstan in sequence
  - Updated CLAUDE.md with pre-commit workflow documentation

## 📦 Dependencies

- **Added**: `symfony/serializer` ^7.3 || ^8.0 - Core serialization/deserialization
- **Added**: `symfony/property-access` ^7.3 || ^8.0 - Required by Symfony Serializer
- **Updated**: Composer dependencies refreshed

## 🔧 Development Tools

- **PHPStan**: Enhanced static analysis configuration
  - Level 7 compliance across codebase
  - Additional exception checks enabled
  - Better error reporting

- **GitHub Workflows**: CI/CD improvements
  - Added Claude Code GitHub Action workflow
  - Improved automated code review capabilities
  - Better integration with development tools

## Full Changelog

**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.20.0...v0.21.0
