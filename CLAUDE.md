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
