# Changelog - YouTube Videos Module

All notable changes to the YouTube Videos Module will be documented in this file.

## [1.0.6] - 2026-10-16

### Changed
- Converted the module to the Joomla 5 dispatcher pattern (`services/provider.php`, `Dispatcher`, `HelperFactory`); removed the legacy `mod_youtubevideos.php` entry file.
- Video cards are now real links to the component's video page, so they are keyboard accessible and crawlable. When the modal player is enabled the shared player script opens the modal instead.
- The module now uses the component's `com_youtubevideos.youtube-player` script instead of shipping its own copy.
- Grid layout uses Bootstrap `row-cols-*` utilities; styles use Bootstrap CSS variables and work in dark mode.
- Module caching uses the `itemid` cache mode.

### Fixed
- Language filtering bound the language tag as an integer, which showed videos from every language.
- Videos outside their publish up/down window, or belonging to an unpublished or restricted playlist, were shown.
- Database errors were displayed to site visitors; they are now logged.
- Missing descriptions were fetched from the YouTube API on every render without a timeout; results are now cached.
- Video descriptions in the modal could execute HTML (the shared player now uses plain text).

### Removed
- The "Show Duration" option, which never had any data to show.

## [1.0.3] - 2025-11-15

### Fixed
- Fixed video player height rendering issue in modal
- Implemented padding-bottom technique for proper 16:9 aspect ratio
- Added absolute positioning for YouTube iframe to ensure proper display
- YouTube player now correctly fills the modal body with appropriate height

## [1.0.2] - 2025-11-15

### Changed
- Increased modal video player size from `modal-lg` to `modal-xl`
- Adjusted modal max-width to 75rem (1200px) on desktop and 85rem (1360px) on larger screens
- Video player automatically scales with modal size maintaining 16:9 aspect ratio

## [1.0.1] - 2025-11-15

### Fixed
- Fixed YouTube player not displaying videos in modal dialog
- Implemented proper YouTube IFrame API initialization using Bootstrap modal events
- Added event listener for `shown.bs.modal` to ensure player initializes when modal is visible
- Improved player cleanup when switching between videos
- Added error handling and console logging for debugging

### Changed
- Created dedicated JavaScript file for module (`media/js/youtube-player.js`)
- Updated module to use its own JavaScript instead of relying on component's script
- Enhanced player initialization with proper event callbacks

## [1.0.0] - 2025-11-15

### Added
- Initial release of YouTube Videos Module for Joomla 5
- Playlist selection from published YouTube playlists in component database
- Responsive grid layout with configurable columns (2, 3, 4, or 6 per row)
- Modal video player using YouTube IFrame API
- Configurable display options for title, description, and duration
- Video ordering options:
  - Custom Order (Ascending/Descending) - Uses ordering field
  - Date-based ordering (Newest/Oldest first)
  - Alphabetical ordering by title (A-Z/Z-A)
- Automatic description retrieval from YouTube API when database description is empty
- Efficient batch API calls for fetching missing descriptions (up to 50 videos per request)
- Optional module heading parameter
- Bootstrap 5 based styling with CSS custom properties
- Multi-lingual support with English language files
- Module caching support for improved performance
- Access level and language filtering
- Responsive design for mobile, tablet, and desktop
- Video thumbnail display with duration badge overlay
- Clean and modern UI matching component design
- Comprehensive README documentation

### Technical Details
- PHP 8.3+ compatibility
- Joomla 5 API compliance
- Namespaced code structure (PSR-4)
- MariaDB compatible queries
- Bootstrap 5 CSS framework integration
- Helper class for data retrieval and processing
- ISO 8601 duration parsing and formatting

### Requirements
- Joomla 5.x
- YouTube Videos Component (com_youtubevideos)
- Published YouTube playlists in database
- Bootstrap 5 (included in Joomla 5)

