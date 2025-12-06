# Changelog - YouTube Single Video Module

All notable changes to this module will be documented in this file.

## [1.2.6] - 2025-11-23

### Fixed
- **CRITICAL**: Fixed `defer` attribute incorrectly placed in `$options` (2nd parameter) instead of `$attribs` (3rd parameter) in `addScript()`
- Defer attribute now correctly added to script tag for proper async loading
- Ensures JavaScript loads without blocking page rendering

## [1.2.5] - 2025-11-23

### Fixed
- **CRITICAL**: Fixed `version` parameter being passed to non-existent 4th argument in `addStyleSheet()` and `addScript()`
- Version parameter now correctly placed in `options` array (2nd parameter) for proper cache-busting
- This ensures CSS/JS updates are properly loaded instead of being cached

## [1.2.4] - 2025-11-23

### Fixed
- Fixed play button visibility issue in Card and Thumbnail modes with multiple instances
- Increased z-index from 10 to 100 to ensure play button appears above all elements
- Added `isolation: isolate` to create proper CSS stacking contexts
- Added positioning to module wrapper and card containers
- Enhanced image display rules to prevent layout issues
- Added `will-change: transform` for better animation performance

## [1.2.3] - 2025-11-23

### Fixed
- Fixed play button not appearing on 2nd and 3rd module instances
- Added `!important` flags to critical CSS properties to prevent Bootstrap overrides
- Increased play button z-index from 2 to 10 for better stacking context
- Added pointer-events handling to ensure clicks work correctly
- Added version parameter to CSS/JS loading to prevent caching issues

## [1.2.2] - 2025-11-23

### Security
- Fixed potential XSS vulnerability by escaping `youtubeId` in iframe src attribute
- Added sanitisation for autoplay parameter to only accept '0' or '1' values

## [1.2.1] - 2025-11-23

### Fixed
- Fixed conflict when multiple module instances are displayed on the same page
- Improved JavaScript event handling to prevent double-binding
- Added unique module ID for better instance isolation
- Enhanced player initialization with MutationObserver for dynamic content

### Changed
- Wrapped JavaScript in IIFE for better encapsulation
- Added initialization checks to prevent duplicate event listeners

## [1.2.0] - 2025-11-xx

### Added
- Initial release with video source options (module or menu parameter)
- Support for embed, card, and thumbnail display modes
- Customisable play button and video information display
- YouTube thumbnail quality selection

