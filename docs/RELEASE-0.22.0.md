## ✨ New Features

- **$all and System Stream Support**: Stream feed iterators now work with `$all` and system streams (`$streams`, `$et-*`, `$ce-*`, ...)
  ```php
  foreach ($eventStore->forwardStreamFeedIterator('$all') as $entryWithEvent) {
      // iterate the global event log
  }
  ```
  - Iterators request feeds with `embed=body` and read events directly from the embedded entry data
  - One HTTP request per page instead of one batch request per page plus one request per event

- **Embedded Events on Entries**: New `Entry::getEmbeddedEvent(): ?Event` method exposes the event embedded in a feed entry when the feed was opened with `EntryEmbedMode::BODY`

## 🐛 Bug Fixes

- **Iteration no longer stops on pages without events**: A feed page consisting entirely of unresolved linkTos (e.g. `$streams` entries pointing into hard-deleted streams) silently ended iteration even when later pages held valid events; the iterator now keeps navigating until it finds a page with events or runs out of pages

- **Stream names are URL-encoded**: Stream names containing `?`, `#`, `/` or other reserved characters were silently mangled — writes to `name?one` and `name?two` both landed in stream `name`. Names are now `rawurlencode`d, which also makes streams with `/` in their name (e.g. `$connectors-mngt/state-projection/checkpoints`) reachable

- **Version conflicts detected without reason phrases**: `WrongExpectedVersionException` was recognized only by the HTTP reason phrase, which does not exist in HTTP/2 (e.g. behind a TLS-terminating proxy); the `Kurrent-CurrentVersion` / `ES-CurrentVersion` response header is now checked as well

- **`valid()` before `rewind()` no longer throws**: Calling `valid()` or `next()` on a freshly created `StreamFeedIterator` threw a typed-property initialization error instead of behaving like an unstarted iterator

## 🔧 Breaking Changes

- **EntryDenormalizer Constructor**: Now requires an `EventDenormalizer` as second argument to build embedded events
  ```php
  // Before
  new EntryDenormalizer($linkDenormalizer)

  // After
  new EntryDenormalizer($linkDenormalizer, $eventDenormalizer)
  ```
  - Applications using `EventStoreFactory` don't need changes

- **Iterator HTTP behavior**: `StreamFeedIterator` now opens feeds with `embed=body` and no longer issues per-event batch requests; entries whose events cannot be resolved (e.g. links into hard-deleted streams) are skipped instead of failing the whole page

## 🛠️ Improvements

- **Event Denormalization**: `EventDenormalizer` handles both wrapped (single event read) and embedded (feed entry) formats, decodes JSON-string `data`/`metaData` fields, and treats empty metadata as `null` consistently across read paths

## 📦 Dependencies

- **Updated**: PHPUnit to ^13.0 (dev dependency)
- **Updated**: Composer dependencies refreshed

## Full Changelog

**Full Changelog**: https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.21.0...v0.22.0
