## 🛠️ Improvements
- **HTTP Batch v3.0 API**: Updated to use improved batch response API with better method names
- **Enhanced Type Safety**: Replaced `getFailedResults()` with `getExceptions()` for direct exception access
- **Cleaner Response Handling**: Replaced filtered `getResults()` with `getResponses()` for typed response handling

## 📦 Dependencies
- Updated `friendsofouro/http-batch-contract` from ^2.0 to ^3.0
- Updated `friendsofouro/http-batch-implementation` from ^2.0 to ^3.0
- Updated `friendsofouro/http-batch-guzzle` from ^2.0 to ^3.0

## 🔧 Breaking Changes
- **HTTP Batch Implementation**: Updated internal batch processing to use v3.0 API (no public API changes)

## Full Changelog
**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.18.0...v0.19.0