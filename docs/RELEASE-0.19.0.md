## ✨ New Features
- **HTTP Batch v3.0 Support**: Upgraded to http-batch-contract v3.0 with improved API semantics
- **Enhanced Exception Handling**: Added comprehensive HttpErrorHandler with dedicated error processing
- **Stream Not Available Exception**: New exception type for better error granularity
- **Comprehensive Unit Testing**: Added extensive test coverage for HttpErrorHandler class

## 🛠️ Improvements
- **Better Error Processing**: Simplified error handling in readEventBatch() method with direct exception access
- **Type Safety**: Enhanced type safety with explicit ResponseInterface type hints
- **API Semantics**: Replaced `getFailedResults()` with `getExceptions()` for direct exception access
- **Response Handling**: Replaced filtered `getResults()` with `getResponses()` for typed response handling
- **Header Modernization**: Updated header names from ES- to Kurrent- prefix
- **Test Coverage**: Refactored tests to use modern PHPUnit attributes and improved coverage

## 📦 Dependencies
- Updated `friendsofouro/http-batch-contract` from ^2.0 to ^3.0
- Updated `friendsofouro/http-batch-implementation` from ^2.0 to ^3.0
- Updated `friendsofouro/http-batch-guzzle` from ^2.0 to ^3.0

## 🔧 Breaking Changes
- **HTTP Batch API**: The underlying HTTP batch API has changed, but the EventStore public interface remains backward compatible
- **Internal Exception Handling**: Internal exception handling flow has been refactored for better reliability

## Full Changelog
**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.18.0...v0.19.0