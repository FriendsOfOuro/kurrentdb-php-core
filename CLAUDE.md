# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

This project uses Docker Compose for development environment and Make for common tasks:

### Environment Management
- `make up` - Start KurrentDB and PHP containers (builds if needed)
- `make down` - Stop all containers
- `make logs` - Follow container logs
- `make install` - Install Composer dependencies inside container

### Code Quality and Testing
- `make test` - Run PHPUnit tests with testdox output
- `make test-coverage` - Run tests with coverage report
- `make cs-fixer` - Fix code style using PHP-CS-Fixer
- `make cs-fixer-ci` - Check code style (dry-run with diff)
- `make phpstan` - Run static analysis (level 5)
- `make benchmark` - Run performance benchmarks

### Running Individual Commands
All PHP commands are executed inside Docker containers using: `docker compose exec php <command>`

Examples:
- `docker compose exec php bin/phpunit tests/Tests/EventStoreTest.php` - Run specific test file
- `docker compose exec php bin/phpstan analyse src/EventStore.php` - Analyze specific file

## Architecture Overview

This is a PHP 8.4+ library that provides a client for KurrentDB (formerly EventStoreDB) HTTP API for event sourcing applications.

### Core Components

**Main Entry Point:**
- `EventStore` - Primary client class implementing `EventStoreInterface`
- Configured with HTTP client and KurrentDB URL (default: `http://admin:changeit@127.0.0.1:2113`)

**Event Handling:**
- `WritableEvent` - Events to be written to streams
- `WritableEventCollection` - Collections of events for atomic writes
- `StreamFeed\Event` - Events read from streams with metadata
- `WritableToStream` - Interface for objects writable to streams

**Stream Management:**
- `StreamFeed\StreamFeed` - Paginated stream representation with navigation links
- `StreamFeed\StreamFeedIterator` - Forward/backward stream iteration
- `StreamFeed\Entry` - Individual stream entries with event URLs
- `StreamFeed\EntryEmbedMode` - Controls event data embedding (NONE, RICH, BODY)
- `StreamFeed\LinkRelation` - Navigation relations (FIRST, LAST, NEXT, PREVIOUS)

**HTTP Layer:**
- `Http\HttpClientInterface` - Abstraction for HTTP clients
- `Http\GuzzleHttpClient` - Guzzle-based implementation with caching support
- Supports PSR-6 cache, filesystem cache, and APCu cache

**Value Objects & Utilities:**
- `ValueObjects\Identity\UUID` - UUID handling
- `ExpectedVersion` - Stream version constants (ANY, NO_STREAM, etc.)
- `StreamDeletion` - Deletion modes (SOFT, HARD)

### Key Patterns

1. **PSR Compliance:** Uses PSR-7 (HTTP messages) and PSR-18 (HTTP client) standards
2. **Optimistic Concurrency:** Stream operations include expected version checking
3. **Batch Operations:** Support for reading multiple events efficiently
4. **Stream Navigation:** AtomPub-style feed navigation with link relations
5. **Error Handling:** Specific exceptions for common scenarios (stream not found, wrong version, etc.)

### Testing Environment

- Uses Docker Compose with KurrentDB container for integration tests
- PHPUnit configuration in `phpunit.xml.dist`
- Test environment variable: `EVENTSTORE_URI=http://admin:changeit@127.0.0.1:2113`
- Tests located in `tests/` directory with namespace `KurrentDB\`

### Code Standards

- PHP-CS-Fixer with Symfony and PSR-12 rules
- PHPStan static analysis at level 5
- Snake case for PHPUnit method names
- Rector for PHP 8.4+ features and code quality improvements
