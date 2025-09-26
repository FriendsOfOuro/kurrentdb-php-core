## 🛠️ Improvements

- **Major Architecture Refactoring**: Complete implementation of facade pattern with service segregation
  - EventStore is now a pure facade delegating to specialized services
  - StreamReader handles all stream reading operations
  - StreamWriter manages stream writing and deletion
  - StreamIteratorFactory creates stream iterators for navigation

- **Dependency Injection**: Full implementation of proper dependency injection
  - HttpErrorHandler is now a shared dependency across services
  - StreamFeedFactory and EntryFactory are injected rather than created internally
  - EventStoreFactory manages all service dependencies and lifecycle

- **Factory Pattern**: EventStoreFactory is now the recommended way to create EventStore instances
  ```php
  $factory = new EventStoreFactory($uriFactory, $requestFactory, $httpClient);
  $eventStore = $factory->create();
  ```

- **Code Quality**: Improved adherence to SOLID principles
  - Interface segregation with focused service interfaces
  - Dependency inversion throughout the service layer
  - Better separation of concerns and testability

- **Developer Experience**: Enhanced documentation and tooling
  - Updated README.md with new architecture examples
  - Added dependency validation with `make check-src-deps`
  - CI pipeline now includes dependency checking

## 🔧 Breaking Changes

- **EventStore Constructor**: Now requires service dependencies instead of HTTP clients
  ```php
  // Before
  $eventStore = new EventStore($uri, $httpClient);

  // After (recommended)
  $factory = new EventStoreFactory($uriFactory, $requestFactory, $httpClient);
  $eventStore = $factory->create();
  ```

- **Service Constructors**: StreamReader and StreamWriter now require additional dependencies
  - HttpErrorHandler must be injected
  - StreamFeedFactory must be injected into StreamReader

## 📦 Dependencies

- Added explicit PSR dependencies: `psr/http-message`, `psr/http-client`, `psr/http-factory`
- Updated to ensure all used interfaces are explicitly declared

## Full Changelog

**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.19.0...v0.20.0