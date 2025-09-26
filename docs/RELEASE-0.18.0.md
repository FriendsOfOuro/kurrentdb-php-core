## New Features: Upgrade to Batch HTTP v2.0

### Changes
- **Batch Processing**: Updated to use `friendsofouro/http-batch-contract` v2.0
- **Dependencies**: Requires `friendsofouro/http-batch-implementation` v2.0
- **Fail-Fast Behavior**: `readEventBatch()` now throws the first exception when any request fails

### What Changed
- **readEventBatch()** method now uses the new `ResponseBatchInterface`
- Batch requests fail immediately if any individual request fails
- Smart retry logic will be implemented in future versions

### Migration Guide
The `readEventBatch()` method behavior remains the same from a consumer perspective - it still returns an array of Event objects or throws an exception. The internal implementation now uses the more robust batch processing system.

### Dependencies
- Requires PHP 8.4+
- `friendsofouro/http-batch-contract` v2.0
- `friendsofouro/http-batch-implementation` v2.0 (provided by `friendsofouro/http-batch-guzzle` v2.0+)

**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.17.0...v0.18.0