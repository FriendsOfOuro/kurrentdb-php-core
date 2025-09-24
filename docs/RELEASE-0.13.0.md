## ✨ New Features

- **Docker development environment** - Added complete Docker Compose setup with PHP 8.4
- **GitHub Actions CI** - Comprehensive testing pipeline with PHPUnit and code validation
- **Makefile workflow** - Standardized development commands for building, testing, and code formatting
- **Modern PHP-CS-Fixer configuration** - PSR-12 and Symfony coding standards with strict types enforcement
- **PSR-6 Cache support** - Enhanced HTTP client with configurable caching strategies
- **Benchmark tooling** - Added performance benchmarking capabilities

## 🛠️ Improvements

- **PHP 8.4 compatibility** - Full upgrade to modern PHP with typed properties and constants
- **Enhanced type safety** - Added `declare(strict_types=1)` across all source files
- **PHPStan Level 5** - Maximum static analysis level for enhanced code quality
- **Improved error handling** - Better return types and exception handling throughout
- **Code modernization** - Applied Rector for automated refactoring and code improvements
- **KurrentDB branding** - Updated from EventStore to KurrentDB naming and content types
- **Dependencies upgrade** - Updated all dependencies to latest secure versions

## 📦 Dependencies

### Updated
- `php: ^8.4` (major upgrade from earlier versions)
- `guzzlehttp/psr7: ^2.8` (from ^1.x)
- `phpunit/phpunit: ^12.3` (from earlier versions)
- Various security updates for Guzzle HTTP components

### Added
- `friendsofphp/php-cs-fixer: ^3.87` for code formatting
- `phpstan/phpstan: ^2.1` for static analysis
- `rector/rector: ^2.1` for automated code refactoring

### Removed
- Travis CI configuration (replaced with GitHub Actions)
- Legacy PHP-CS configuration files
- Scrutinizer configuration

## 🐳 Docker & Development

- **Docker Compose** - Complete development environment with PHP 8.4-cli
- **Makefile commands** - Standardized commands: `up`, `down`, `install`, `test`, `phpstan`, `cs-fixer`
- **Health checks** - Proper service dependency management
- **Optimized builds** - Multi-stage Docker builds with Composer optimization

## 🔧 Breaking Changes

- **Minimum PHP version** - Now requires PHP 8.4+
- **Content-Type headers** - Updated to use `application/vnd.kurrent.*` instead of EventStore formats
- **Method signatures** - Enhanced type hints and return types may require updates
- **Namespace consistency** - All classes now properly use `KurrentDB` namespace

## 🛡️ Security

- **Updated dependencies** - All dependencies updated to latest secure versions
- **Enhanced validation** - Improved input validation and error handling

## Full Changelog

https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.12.0...v0.13.0