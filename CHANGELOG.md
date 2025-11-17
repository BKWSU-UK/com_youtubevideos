# Changelog

All notable changes to the YouTube Videos Component for Joomla will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.21] - 2025-11-17

### Fixed
- **Pagination Navigation:** Fixed issue where clicking pagination links would jump to a different menu item
- Pagination links now correctly preserve the current menu item ID (`Itemid`) to stay on the same page
- Form action also preserves `Itemid` to maintain correct context when using filters

### Changed
- **Pagination Display:** Pagination now shows a maximum of 10 page links with ellipsis for better usability
- Active pagination link now has primary colour background with white text and increased font weight
- Added hover effect to pagination links with subtle primary colour
- Pagination is now centred on the page with improved spacing

### Technical
- Added `Itemid` preservation logic in `site/tmpl/videos/default.php`
- All pagination URLs (previous, page numbers, next) now include the current `Itemid` parameter
- Form action URL also includes `Itemid` to maintain context when submitting filters
- Implemented smart pagination algorithm that displays maximum 10 page links
- Algorithm automatically adjusts when current page is near start or end
- Enhanced active pagination link styling in `media/css/component.css`
- Added explicit white text colour and increased font weight for active page
- This fixes the issue where Joomla's router would select a different menu item when multiple menu items point to the same view type

## [1.0.20] - 2025-11-15

### Fixed
- **Filters:** Fixed category and playlist dropdown filters in videos list view showing IDs instead of names
- Filter dropdowns now correctly display category and playlist titles when selected

### Technical
- Added `key_field="id"` and `value_field="title"` attributes to category and playlist SQL fields in `admin/forms/filter_featured.xml`
- SQL field type now properly maps database columns to display values and option values
- Configuration matches the working implementation in `batch_videos.xml` and `video.xml`
- Created `build.sh` script to automate component packaging

## [1.0.19] - 2025-11-15

### Removed
- **Categories:** Removed YouTube tag field from category management interface as it was not being used
- Removed youtube_tag from category edit form, list view, and all related models

### Fixed
- **Database:** Added default empty string to youtube_tag column to fix "Field 'youtube_tag' doesn't have a default value" error when saving categories

### Technical
- Removed field from `admin/forms/category.xml`
- Removed field rendering from `admin/tmpl/category/edit.php`
- Removed validation check from `admin/src/Table/CategoryTable.php`
- Removed from filter fields, select query, and search in `admin/src/Model/CategoriesModel.php`
- Removed from select query in `admin/src/Model/DashboardModel.php`
- Removed column header and data from `admin/tmpl/categories/default.php`
- Added database migration `1.0.19.sql` to set default value for youtube_tag column
- Updated `install.mysql.sql` to include default empty string for youtube_tag column
- Database column still exists for data preservation but is no longer used in the interface

## [1.0.18] - 2025-11-15

### Fixed
- **Critical:** Fixed "Form::loadForm could not load file" error when editing videos in admin
- Video edit form now loads correctly with all required fields

### Technical
- Created complete `admin/forms/video.xml` form definition (was previously empty)
- Added all required fields: title, alias, youtube_video_id, description, custom_thumbnail, category_id, playlist_id, tags, featured, published, access, language, ordering, timestamps, publish_up, publish_down, and params
- Form now properly maps to the `#__youtubevideos_featured` table structure
- Updated form to use correct existing language strings from `com_youtubevideos.ini`

## [1.0.17] - 2025-11-15

### Fixed
- **Pagination:** Fixed issue where all videos were being displayed instead of only the first page
- Videos list now properly respects pagination settings and displays only the configured number of videos per page

### Technical
- Added proper handling of `list.start` parameter in `VideosModel::populateState()`
- Calls `parent::populateState()` **first**, then overrides pagination state to prevent parent from resetting custom values
- Model now checks both `limitstart` and `start` URL parameters for pagination offset
- Uses sentinel value (-1) to detect if `limitstart` parameter was provided, avoiding filter type conflicts
- `limitstart` parameter correctly takes priority over `start` when both are provided
- Ensures `limitstart` is always non-negative even if a negative value is somehow passed
- This ensures pagination works correctly when users navigate between pages

## [1.0.16] - 2025-11-14

### Added
- **Video Tagging:** Video edit form now includes a tags field so editors can attach comma-separated tags without leaving the screen
- **Frontend Tag Filter:** Visitors can filter the videos list by a published tag using the Search Tools sidebar

### Changed
- Video model persists tag relationships via the `#__youtubevideos_video_tag_map` table and auto-creates tag records when needed

## [1.0.15] - 2024-11-07

### Fixed
- **Installation Messages:** Added missing translation strings for installer script messages
- Install, update, and uninstall messages now display properly instead of showing language keys

### Technical
- Added `COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_INSTALL`, `COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_UPDATE`, and `COM_YOUTUBEVIDEOS_INSTALLERSCRIPT_UNINSTALL` constants to `com_youtubevideos.sys.ini`

## [1.0.14] - 2024-11-07

### Fixed
- **Admin Menu Highlighting:** Dashboard menu item no longer remains highlighted when other menu items are active
- Dashboard menu link now explicitly specifies `view=dashboard` parameter for proper menu highlighting

### Technical
- Updated XML manifest submenu configuration to include explicit view parameter for Dashboard link
- This ensures Joomla correctly identifies the active menu item based on the current view

## [1.0.13] - 2024-11-07

### Changed
- **Video Player Modal:** Modal title now displays the actual video title instead of the generic "Video Player" text
- **Video Description:** Full video description is now displayed below the video player in the modal
- **Modal Aesthetics:** Enhanced modal design with gradient header, improved spacing, and better overall visual appeal
- Modal width set to 900px (large size) for comfortable viewing without being too wide
- Added custom scrollbar styling for the description section

### Technical
- Added `data-video-title` and `data-video-description` attributes to video items in the videos list
- JavaScript now updates modal title and description dynamically when a video is clicked
- Added new CSS styles for the video description section with custom scrollbar
- Modal styling uses Bootstrap CSS variables (--bs-primary, --bs-light, etc.) for consistent theming
- Added `COM_YOUTUBEVIDEOS_DESCRIPTION` language string
- Description content preserves line breaks and is scrollable when longer than 300px

## [1.0.12] - 2024-11-06

### Fixed
- "Videos per Row" menu parameter now correctly controls the grid layout (2, 3, 4, or 6 columns)
- Added pagination controls with visible page numbers and navigation arrows
- Pagination now displays when there are multiple pages of videos

### Changed
- Removed redundant "Videos per Page" menu parameter - users control this via the list limit dropdown on the page itself

### Technical
- Template now applies dynamic CSS classes based on `videos_per_row` parameter
- Added responsive CSS rules for different grid column layouts
- Created custom pagination template with visible page numbers and arrows
- View now loads pagination object from model
- List limit dropdown now dynamically shows only multiples of `videos_per_row` (e.g., if 3 per row: 3, 6, 9, 12, etc.)
- Model automatically rounds up selected limits to nearest multiple to ensure complete rows
- Default list limit is set to 4th multiple (e.g., 12 for 3 per row, 16 for 4 per row)

## [1.0.11] - 2024-11-06

### Fixed
- Category changes on videos now save correctly - the `category_id` field properly updates when changed or cleared

### Technical
- Changed `FeaturedTable::store()` default parameter from `$updateNulls = false` to `$updateNulls = true`
- This ensures nullable fields like `category_id` and `playlist_id` are properly updated in the database when set to NULL

## [1.0.10] - 2024-11-06

### Fixed
- Batch controller no longer calls the deprecated `getBootableContainer()` method, preventing fatal errors on Joomla 5

### Technical
- Switched database retrieval in `VideosController::batch()` to `Factory::getContainer()->get('DatabaseDriver')`

## [1.0.9] - 2024-11-06

### Fixed
- Batch modal now opens reliably by using Joomla's `HTMLHelper::_('bootstrap.modal')` to ensure required assets are loaded
- Batch toolbar button now uses data attributes instead of a JavaScript constructor that was unavailable in some Joomla configurations

### Technical
- Switched toolbar button to `linkButton` with Bootstrap 5 attributes
- Removed `listCheck` override to avoid conflicting JavaScript and added modal initialisation via Joomla's Bootstrap helper

## [1.0.8] - 2024-11-06

### Fixed
- **Batch Modal Not Opening:** Fixed batch button not triggering the modal when clicked
- Changed from `popupButton()` to `standardButton()` with proper Bootstrap modal trigger

### Technical
- Added hidden trigger button with Bootstrap 5 `data-bs-toggle` and `data-bs-target` attributes
- Batch toolbar button now clicks the hidden trigger via JavaScript
- This properly initializes the Bootstrap modal using Bootstrap's native modal API

## [1.0.7] - 2024-11-06

### Changed
- **Video ID Links:** Video IDs in the admin videos list are now clickable links that open the actual YouTube video in a new tab
- Added security attributes (`rel="noopener noreferrer"`) to YouTube links
- Added "Watch on YouTube" tooltip to video ID links

### Technical
- Updated `admin/tmpl/videos/default.php` to wrap Video ID in anchor tag
- Added `COM_YOUTUBEVIDEOS_WATCH_ON_YOUTUBE` language string

## [1.0.6] - 2024-11-06

### Security
- **OAuth Scope Restriction:** Changed OAuth scope from `youtube.force-ssl` back to `youtube.readonly` following the principle of least privilege
- The component only performs read operations on YouTube API (fetching videos, channels, and playlists), so write permissions are unnecessary

### Technical
- Updated OAuth authorization scope in `OauthController.php` to use readonly permissions
- This reduces security risk by limiting the permissions granted to the application

## [1.0.5] - 2024-11-06

### Fixed
- **Critical:** Fixed "Call to protected method loadForm()" error in Videos HtmlView
- Batch button now appears in the "Change Status" dropdown menu following Joomla standards
- Batch modal now uses Bootstrap modal structure with correct selector

### Changed
- Moved batch button from standalone to dropdown child toolbar (following Joomla best practices)
- Changed modal implementation from `joomla-modal` to Bootstrap modal with id `collapseModal`
- Batch form now loads using `Form::getInstance()` directly instead of calling protected model method

### Technical
- Updated `loadBatchForm()` to use `Form::getInstance()` for proper form loading
- Added `Form` class to imports in HtmlView
- Batch button now uses `popupButton()` method within the status dropdown
- Modal moved inside form tag for proper submission handling

## [1.0.4] - 2024-11-06

### Added
- **Batch operations for videos** - Assign multiple videos to a category, playlist, access level, or language in one go
- Batch button in Videos admin toolbar
- Batch modal with category, playlist, access level, and language selectors
- Option to remove category or playlist assignments in batch operations

### Fixed
- Category filtering in menu items now works correctly - videos are properly filtered by the selected category from the database
- Playlist filtering in menu items now works correctly

### Changed
- **Frontend videos are now loaded from the database** (`#__youtubevideos_featured` table) instead of YouTube API
- Videos view now respects Joomla's language and access level filters
- Improved search functionality - now searches both title and description in the database

### Technical
- Completely rewrote `VideosModel::getListQuery()` to query from database instead of YouTube API
- Added proper filtering by `category_id` and `playlist_id` from menu item parameters
- Removed dependency on YouTube API for frontend video listing
- Videos are ordered by `ordering` field and creation date
- Thumbnails are generated from YouTube video IDs or use custom thumbnails if set
- Added `batch()` method to VideosController for bulk operations
- Created `batch_videos.xml` form for batch modal
- Added batch template (`default_batch.php`) for Videos view

## [1.0.3] - 2024-11-06

### Added
- Pagination support for video synchronisation - now syncs ALL videos, not just the first 50
- Duplicate detection and handling during sync process
- Enhanced sync reporting showing published/unpublished video breakdown
- **Comprehensive diagnostic logging** to identify why videos are skipped during sync
- **Skip tracking** - sync message now shows if videos were skipped and why
- Database migration to add unique constraint on `youtube_video_id` (prevents future duplicates)
- Cleanup utility script (`cleanup_duplicates.php`) for identifying and removing existing duplicates
- Detailed logging for pagination progress during sync

### Changed
- **Dashboard now includes unpublished videos in total count** with breakdown showing published/unpublished
- Video sync now fetches all pages from YouTube API (up to 1,000 videos with safety limit)
- Sync success message now shows: "X added, Y updated. Total in database: Z (A published, B unpublished)"
- Improved duplicate handling - updates all duplicate records during sync
- **Admin menu label changed from "Featured Videos" to "Videos"** for clearer navigation
- Component version updated to 1.0.3

### Fixed
- Issue where only first 50 videos were synced despite having more videos in channel/playlist
- Discrepancy between sync count and dashboard total due to unpublished videos not being reported
- Potential duplicate video entries during sync from paginated API responses

### Technical
- Added `pageToken` parameter to all YouTube API fetch methods
- Implemented `do-while` pagination loop in `syncVideos()` method
- Added safety limit of 20 pages (1,000 videos) to prevent infinite loops
- Enhanced database queries to detect and report duplicate entries

## [1.0.0] - 2024-11-05

### Added
- Initial release of YouTube Videos Component for Joomla 5
- YouTube Data API v3 integration
- Frontend video display with grid layout
- Backend dashboard with statistics and analytics
- Featured videos CRUD with drag-and-drop ordering
- Categories management with YouTube tag filtering
- Playlists management
- Video statistics tracking (views, likes)
- Intelligent caching system with configurable duration
- Multi-language support (en-GB included)
- Responsive design for mobile and desktop
- Modal video player using YouTube IFrame API
- Search and filter functionality
- ACL integration with granular permissions
- Component configuration options
- Database schema with proper indexes and foreign keys
- Installation and update scripts
- Error handling and logging throughout
- Security features (SQL injection prevention, XSS protection, CSRF tokens)
- PHPDoc documentation throughout codebase

### Security
- All database queries use parameter binding
- Input validation on all user inputs
- XSS protection using Joomla's escape methods
- CSRF token verification
- ACL checks throughout

### Technical
- PHP 8.3+ with strict type declarations
- Joomla 5.0+ compatibility
- PSR-12 coding standards
- Dependency injection via service provider
- MVC architecture
- Bootstrap 5 styling
- Web Asset Manager integration
- Comprehensive error handling

### Documentation
- Complete README.md with installation and usage instructions
- Inline code documentation
- Database schema documentation
- File structure overview

## [Unreleased]

### Planned Features
- Video comments integration
- Live stream support
- Advanced analytics dashboard
- Video upload functionality
- Subtitle/caption support
- Video recommendations engine
- Social sharing integration
- Export functionality (CSV, JSON)
- CLI commands for maintenance tasks
- REST API endpoints
- Custom video thumbnails upload
- Video categories hierarchy
- Tags autocomplete
- Batch operations for videos
- Import from multiple channels
- Scheduled video publishing
- Video series/collections
- Related videos widget
- Search autocomplete
- Video bookmarking
- User playlists (frontend)

---

## Version History Summary

- **1.0.21** (2025-11-17) - Pagination Navigation Fix (Itemid Preservation)
- **1.0.20** (2025-11-15) - Filter Dropdowns Fix (Category/Playlist Names)
- **1.0.19** (2025-11-15) - Category YouTube Tag Field Removal
- **1.0.18** (2025-11-15) - Video Edit Form Fix
- **1.0.17** (2025-11-15) - Pagination Fix for Videos List
- **1.0.16** (2025-11-14) - Video Tagging and Frontend Tag Filter
- **1.0.15** (2024-11-07) - Installation Messages Translation Fix
- **1.0.14** (2024-11-07) - Admin Menu Highlighting Fix
- **1.0.13** (2024-11-07) - Video Player Modal Enhancement
- **1.0.12** (2024-11-06) - Menu Parameters Fix (Videos per Row/Page)
- **1.0.11** (2024-11-06) - Category Save Fix
- **1.0.10** (2024-11-06) - Batch Controller DB Retrieval Fix
- **1.0.9** (2024-11-06) - Batch Modal Asset Fix
- **1.0.8** (2024-11-06) - Batch Modal Trigger Fix
- **1.0.7** (2024-11-06) - Video ID Clickable Links
- **1.0.6** (2024-11-06) - OAuth Security Fix (Scope Restriction)
- **1.0.5** (2024-11-06) - Batch Form Loading Fix
- **1.0.4** (2024-11-06) - Category Filtering Fix
- **1.0.3** (2024-11-06) - Pagination Support & Duplicate Detection
- **1.0.0** (2024-11-05) - Initial Release

---

## Migration Notes

### From 1.0.0 to 1.0.3
1. **Check for Duplicates (Optional but Recommended)**:
   - Before updating, run the `cleanup_duplicates.php` script to check for duplicate video entries
   - Upload the script to your Joomla root directory
   - Run via CLI: `php cleanup_duplicates.php` or access via browser (Super User only)
   - The script runs in DRY RUN mode by default (safe, no changes)
   - If duplicates are found, backup your database and run with `$dryRun = false`

2. **Update Component**:
   - Install the new version via Joomla's Extension Manager
   - The database migration (1.0.3.sql) will run automatically
   - This adds a unique constraint on `youtube_video_id` to prevent future duplicates

3. **Verify Sync**:
   - After updating, run the video sync from the dashboard
   - You should now see all videos synced with detailed breakdown
   - Check the sync message for published/unpublished counts

4. **Security**:
   - Delete `cleanup_duplicates.php` after use for security

### From No Previous Version
This is the initial release. Follow the installation instructions in README.md

---

## Breaking Changes

### Version 1.0.0
None - Initial release

---

## Support

For questions, bug reports, or feature requests:
- Email: allan@bkconnect.net
- Website: https://www.brahmakumaris.org

---

## Credits

Developed by Allan Schweitz for Brahma Kumaris World Spiritual University
Copyright © 2024 Brahma Kumaris. All rights reserved.

