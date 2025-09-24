## 🔧 Breaking Changes

- **HTTP Client Interface** - Replaced custom `HttpClientInterface` with PSR-18 compliant interfaces:
  - `EventStore` constructor now requires `UriFactoryInterface`, `RequestFactoryInterface`, and `ClientInterface`
  - Removed internal `GuzzleHttpClient` implementation
  - Users must now provide their own HTTP client and factories

- **Stream Write Return Type** - `writeToStream()` now returns `StreamWriteResult` value object instead of `false|int`
  - Provides more structured response with version information
  - Eliminates ambiguous return types

- **Interface Segregation** - Refactored `EventStoreInterface` following Interface Segregation Principle:
  - Split into specialized interfaces: `StreamReaderInterface`, `StreamWriterInterface`, `EventReaderInterface`, `StreamIteratorFactoryInterface`, `HttpDiagnosticsInterface`
  - Main interface now extends all specialized interfaces for backward compatibility

- **Iterator Page Limits** - Added optional `pageLimit` parameter to stream iterator methods:
  - `forwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX)`
  - `backwardStreamFeedIterator(string $streamName, int $pageLimit = PHP_INT_MAX)`

## ✨ New Features

- **Batch Iterator with Page Limits** - Enhanced `StreamFeedIterator` with configurable page limits for better memory management
- **Improved Error Handling** - Comprehensive error handling in `writeToStream` method with specific exceptions for different HTTP status codes
- **New Exception Hierarchy** - Added structured exception hierarchy:
  - `EventStoreException` - Base for all EventStore errors
  - `NetworkException` - Network and server-related errors
  - `StreamException` - Stream-specific errors
  - `WriteException` - Write operation errors
- **StreamGoneException** - New exception for HTTP 410 Gone responses

## 🛠️ Improvements

- **External HTTP Client Support** - Library now uses external HTTP client contracts for better flexibility and testability
- **Code Quality** - Added `declare(strict_types=1)` to entire codebase for better type safety
- **Method Visibility** - Fixed method visibility order throughout codebase following PSR standards
- **Formatting** - Improved code formatting and consistency across all files

## 📦 Dependencies

- **Removed**: `guzzlehttp/psr7`, `php-http/httplug`, `kevinrob/guzzle-cache-middleware`, `symfony/cache`
- **Added**:
  - `friendsofouro/http-batch-contract` ^1.0
  - `psr/http-client-implementation` *
  - `psr/http-factory-implementation` *
  - `psr/http-message-implementation` *
- **Development**: Added `friendsofouro/http-batch-guzzle` ^1.0 for testing

## 🔧 Development Tools

- **PHP-CS-Fixer** - Enhanced configuration with self-fixing capabilities
- **Makefile** - Added new `bash` target for development workflow
- **Rector** - Added rector task to Makefile for automated refactoring

## Full Changelog

https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.15.1...v0.16.0