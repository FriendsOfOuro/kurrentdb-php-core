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
- `make before-push` - **Run before pushing commits** - executes cs-fixer, test, and phpstan in sequence to ensure code quality

### Running Individual Commands
All PHP commands are executed inside Docker containers using: `docker compose exec php <command>`

Examples:
- `docker compose exec php bin/phpunit tests/Integration/EventStoreTest.php` - Run specific test file
- `docker compose exec php bin/phpunit --filter test_it_should_read_stream_feed` - Run specific test method
- `docker compose exec php bin/phpstan analyse src/EventStore.php` - Analyze specific file
- `docker compose exec php bin/rector process src/EventStore.php --dry-run` - Preview Rector changes for a file

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
- Uses `FriendsOfOuro\Http\Batch\ClientInterface` from `friendsofouro/http-batch-contract`
- PSR-18 compliant HTTP client with batch request support
- `Http\Auth\Credentials` - Authentication handling extracted from URI
- `Http\ResponseCode` - HTTP response code constants
- Supports concurrent batch requests for improved performance

**Value Objects & Utilities:**
- `ValueObjects\Identity\UUID` - UUID handling
- `ExpectedVersion` - Stream version constants (ANY, NO_STREAM, etc.)
- `StreamDeletion` - Deletion modes (SOFT, HARD)

### Key Patterns

1. **Interface Segregation:** Core functionality split into focused interfaces:
   - `StreamReaderInterface` - Stream reading operations
   - `StreamWriterInterface` - Stream writing and deletion
   - `EventReaderInterface` - Event reading and batch operations
   - `StreamIteratorFactoryInterface` - Stream iteration factories
   - `HttpDiagnosticsInterface` - HTTP diagnostics
2. **PSR Compliance:** Uses PSR-7 (HTTP messages), PSR-18 (HTTP client), and PSR-17 (HTTP factories)
3. **Optimistic Concurrency:** Stream operations include expected version checking
4. **Batch Operations:** Support for reading multiple events efficiently via `ClientInterface::sendBatch()`
5. **Stream Navigation:** AtomPub-style feed navigation with link relations
6. **Error Handling:** Specific exceptions for common scenarios with dedicated handlers
7. **Factory Pattern:** Dedicated factories for creating stream feeds and entries

### Testing Environment

- Uses Docker Compose with KurrentDB container for integration tests
- PHPUnit configuration in `phpunit.xml.dist` with strict settings
- Test suites: `unit` (tests/Unit) and `integration` (tests/Integration)
- Environment variable: `EVENTSTORE_URI=http://admin:changeit@eventstore.db:2113` (internal Docker network)
- Tests namespace: `KurrentDB\Tests\`
- Base test case: `tests/Integration/TestCase.php` for integration tests
- Xdebug enabled in Docker for coverage reports

### Code Standards

- PHP-CS-Fixer with Symfony and PSR-12 rules
- PHPStan static analysis at level 7 (strict mode)
- Snake case for PHPUnit method names (configured in `.php-cs-fixer.php`)
- Rector for PHP 8.4+ features with prepared sets: deadCode, codeQuality, typeDeclarations
- Strict types declaration enforced in all PHP files
- Chained method calls on new lines for better readability

**Pre-commit Workflow:**
Always run `make before-push` before committing and pushing changes. This ensures:
1. Code style is automatically fixed (cs-fixer)
2. All tests pass (test)
3. Static analysis passes (phpstan)

This catches issues early and maintains code quality across all contributions.

## Release Management

### Creating New Releases

This project follows [Semantic Versioning (SemVer)](https://semver.org/):
- **MAJOR** (X.0.0) - Breaking changes that require user action
- **MINOR** (0.X.0) - New features that are backward compatible
- **PATCH** (0.0.X) - Bug fixes and improvements that are backward compatible

This project maintains detailed release notes for each version. Follow these steps to create a new release:

#### 1. Analyze Changes and Determine Version
First, identify the version range and examine the changes to determine the appropriate version number:

```bash
# Get the latest tag
git tag | sort -V | tail -1

# View commits since last release
git log <last-tag>..HEAD --oneline --no-merges

# Generate detailed diff statistics
git diff <last-tag>..HEAD --stat

# Analyze specific changes for breaking changes
git diff <last-tag>..HEAD -- src/ --name-status
```

**Automatic Version Determination:**
Analyze the changes and automatically determine the version bump according to Semantic Versioning:

- **PATCH (0.0.X)** - If changes only include:
  - Bug fixes without API changes
  - Internal code improvements
  - Documentation updates
  - Test improvements
  - Development tool updates

- **MINOR (0.X.0)** - If changes include:
  - New public methods or classes
  - New features that maintain backward compatibility
  - New optional parameters with defaults
  - Deprecation warnings (but not removals)

- **MAJOR (X.0.0)** - If changes include:
  - Removed or renamed public methods/classes
  - Changed method signatures (parameters, return types)
  - Changed class constructors
  - Removed or changed public properties
  - Changed behavior of existing methods
  - Minimum PHP version requirements changes
  - Required dependency major version updates

**When to ask for confirmation:**
- If interface changes are detected but it's unclear if they break compatibility
- If new required parameters are added to existing methods
- If enum values are changed or removed
- If constructor signatures change
- If unclear whether a change constitutes a breaking change

**Example analysis:**
```bash
# Check for interface/class signature changes
git diff <last-tag>..HEAD -- src/ | grep -E "^\+.*public function|^\-.*public function"

# Check for removed files
git diff <last-tag>..HEAD --name-status | grep "^D"

# Check composer.json for dependency changes
git diff <last-tag>..HEAD -- composer.json
```

#### 2. Create Release Notes
Create a new file `docs/RELEASE-X.X.X.md` following the established format:

**Structure to follow:**
- Start directly with `## ✨ New Features` (no H1 title)
- Use sections: `## ✨ New Features`, `## 🛠️ Improvements`, `## 🔧 Breaking Changes`, `## 📦 Dependencies`, `## 🛡️ Security`
- Include `## Full Changelog` with GitHub compare URL

**Reference existing files:**
- Check `docs/RELEASE-0.13.0.md` for major version example
- Check `docs/RELEASE-0.15.1.md` for patch version example
- Follow the emoji conventions and section structure

#### 3. Commit Release Notes
```bash
git add docs/RELEASE-X.X.X.md
git commit -m "Add release notes for vX.X.X"
git push
```

#### 4. Create GitHub Release
Use GitHub CLI to create the release with the markdown content:
```bash
gh release create vX.X.X --notes-file docs/RELEASE-X.X.X.md --title "KurrentDB PHP Client X.X.X"
```

**Important:** The release notes markdown should NOT include an H1 title to avoid duplication in GitHub releases.

### AI Assistant Instructions

When asked to create a release, follow this automated workflow:

1. **Automatically analyze changes** using the git commands above
2. **Determine version number** based on the semantic versioning rules
3. **Ask for confirmation only** when changes are ambiguous regarding breaking compatibility
4. **Auto-generate** the release notes file following existing patterns
5. **Execute** the full release process (commit, push, create GitHub release)

**Example decision tree:**
- Found only bug fixes, doc updates, test changes → **PATCH**
- Found new public methods, optional parameters → **MINOR**
- Found removed methods, changed signatures, PHP version bump → **MAJOR**
- Found interface changes but unclear impact → **Ask user for clarification**

**When asking for clarification, provide:**
- Specific changes that are unclear
- Potential impact assessment
- Suggested version bump with reasoning

#### 5. Verify Release
Check that the release appears correctly on GitHub:
- Verify title is clean (no duplication)
- Ensure all sections are properly formatted
- Confirm the changelog link works

### Release Note Guidelines

- **New Features** (✨) - New functionality, major additions
- **Improvements** (🛠️) - Enhancements to existing features, performance improvements
- **Breaking Changes** (🔧) - Changes that require user action or may break existing code
- **Dependencies** (📦) - Dependency updates, additions, or removals
- **Security** (🛡️) - Security-related fixes and improvements
- **Documentation** (📚) - Documentation updates and improvements
- **Development Tools** (🔧) - Changes to development workflow, CI/CD, tooling
