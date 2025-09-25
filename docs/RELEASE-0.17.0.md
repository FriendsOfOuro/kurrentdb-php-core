## ✨ New Features

- **Factory Pattern Implementation**: Added `EntryFactory` and `StreamFeedFactory` for improved object creation and dependency management
- **Enhanced Credentials Handling**: New `Credentials` class for better authentication management with proper string conversion support
- **Link Value Object**: Added `Link` class to encapsulate link relations and URIs, improving type safety
- **Extended Link Relations**: Added `SELF` and `EDIT` enum values to `LinkRelation` for broader AtomPub compatibility

## 🛠️ Improvements

- **Dramatically Enhanced Type Safety**: Upgraded PHPStan analysis from level 5 to level 7, adding comprehensive type hints throughout the codebase
- **Better Interface Design**: Updated `EventReaderInterface::readEvent()` to accept `UriInterface` instead of string for stronger typing
- **Optimized Array Operations**: Enhanced batch operations with `array_filter()` to remove null values and improve reliability
- **Test Organization**: Reorganized test suite into clear Unit/Integration structure with separate test suites in PHPUnit configuration
- **Dead Code Elimination**: Removed unused `unparse_url()` function and related utilities
- **Improved Documentation**: Enhanced method signatures with proper parameter and return type annotations

## 📦 Dependencies

- **Updated Development Dependencies**: Added `symfony/var-dumper` ^7.3 for better debugging capabilities
- **Composer Configuration**: Updated autoloader configuration for better PSR-4 compliance
- **PHPStan Memory Optimization**: Increased memory limit to 1G in Makefile for complex static analysis

## 🔧 Development Tools

- **Enhanced Static Analysis**: PHPStan now runs at level 7 with comprehensive type checking and better error detection
- **Improved Test Coverage**: All tests now pass with no incomplete tests, including proper unauthorized access testing
- **Better CI Configuration**: Optimized PHPUnit and PHPStan configurations for more reliable builds
- **Cleaned Configuration**: Removed obsolete `.scrutinizer.yml` configuration

## Full Changelog

**Compare**: [v0.16.0...v0.17.0](https://github.com/friendsofouro/kurrentdb-php-core/compare/v0.16.0...v0.17.0)