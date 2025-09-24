## 🛠️ Improvements

- **PHP 8.4+ enforcement** - Dropped PHP 8.3 support and fully embraced PHP 8.4 features
- **Enhanced type safety** - Made `EntryEmbedMode` parameter non-nullable for better type consistency
- **Documentation updates** - Improved README with better examples and configuration guidance
- **Configuration cleanup** - Removed misleading configuration examples for clearer documentation

## 🔧 Breaking Changes

- **PHP version requirement** - Now strictly requires PHP 8.4+ (dropped 8.3 support)
- **EntryEmbedMode parameter** - No longer accepts `null`, defaults to `EntryEmbedMode::NONE`

## 📚 Documentation

- **README improvements** - Enhanced documentation with clearer examples and setup instructions
- **Configuration cleanup** - Removed confusing configuration examples that could mislead developers

## 🛡️ Code Quality

- **Version consistency** - Updated all configuration files to reflect PHP 8.4+ requirement
- **Type strictness** - Enhanced type safety by removing nullable parameters where inappropriate

## Full Changelog

https://github.com/FriendsOfOuro/kurrentdb-php-core/compare/v0.14.0...v0.15.0