# YouTube Videos Component for Joomla 5

A professional Joomla 5 component for displaying and managing YouTube videos from specific channels and playlists on your website.

## Description

The YouTube Videos Component integrates seamlessly with the YouTube Data API v3 to fetch, display, and manage videos from your YouTube channels and playlists. It provides a comprehensive backend management system and an elegant frontend display with filtering and search capabilities.

## Features

### Frontend
- Video grid (videos list and category views) with search, tag filter, ordering and pagination
- Video cards are real links to each video's page, with a modal YouTube player as an enhancement (keyboard accessible, crawlable)
- Playlist view with a main player and a sidebar that switches videos without reloading (Back/Forward supported)
- Single video view with optional recipe (ingredients and method) and related videos
- SEO: unique titles and meta descriptions, one canonical URL per page, Open Graph/Twitter tags and JSON-LD (`VideoObject`, `ItemList`, `BreadcrumbList`)
- Respects Joomla access levels, publish up/down dates and content language
- Bootstrap 5 markup, dark-mode compatible styles, fully translatable

### Backend
- **Dashboard**: statistics, popular videos, cache and system status, and **Sync Now** to import videos from YouTube
- **Videos**: manage synced and manually added videos, feature/unfeature, tags, recipes, batch category/playlist assignment
- **Categories** and **Playlists**: organise videos
- **Import/Export**: XML import and export of categories, playlists and videos; JSON recipe import
- **OAuth**: optional Google sign-in to sync private/unlisted videos from your own channel
- ACL integration and response caching

### Package contents
| Extension | Purpose |
|---|---|
| `com_youtubevideos` | The component |
| `mod_youtubevideos` | Grid of videos from a playlist |
| `mod_youtube_single` | A single video |
| `plg_system_youtubevideos` | Canonical/social-tag clean-up on component pages, and a video picker for menu items |

## Requirements
- Joomla 5.0+
- PHP 8.3+
- MySQL or MariaDB
- A YouTube Data API v3 key

## Installation

### Step 1: Get a YouTube API Key
1. Visit the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the YouTube Data API v3
4. Create credentials (API Key)
5. Restrict the API key to YouTube Data API v3 (recommended)

### Step 2: Install the Package
1. Build the package with `./build.sh` (or use a released `pkg_youtubevideos.zip`)
2. In the Joomla administrator, go to **System** → **Install** → **Extensions**
3. Upload `pkg_youtubevideos.zip`. This installs the component, both modules and the system plugin, and creates the database tables
4. The system plugin is enabled automatically on a fresh install and when upgrading from a version before 1.0.45

### Step 3: Configure
1. Go to **Components** → **YouTube Videos** → **Options**
2. Enter your API key and Channel ID (find it at https://www.youtube.com/account_advanced), and optionally a Playlist ID to sync from
3. Optionally set up OAuth (see `docs/OAUTH_SETUP_GUIDE.md`); the redirect URI to register with Google is shown on the OAuth tab
4. Configure display and cache settings and save
5. Open the **Dashboard** and click **Sync Now** to import your videos

## Configuration Options

### Basic Settings
- **YouTube API Key** (required), **Channel ID** (required), **Playlist ID** (optional; when set, sync imports this playlist instead of the channel uploads)

### Display Settings
These are site-wide defaults; menu items can override most of them.
- **Videos per Page** (default: 12)
- **Grid Columns**: 2, 3 or 4 columns on desktop (menu items can also choose 6)
- **Show Description**, **Show Published Date**, **Show View Count**

### Cache Settings
- **Enable Cache** and **Cache Time** (minutes) for YouTube API responses

## Usage

### Syncing Videos
**Dashboard** → **Sync Now** imports videos from the configured playlist or channel and refreshes their statistics (views, likes, comments).
- New public videos are published; private or unlisted videos are added unpublished.
- Once you edit a video's title or description in the admin, sync stops overwriting them. You can also control this with the video's **Keep Local Title and Description** switch.

### Menu Items
Under **Menus** → *your menu* → **New** → **YouTube Videos**:
- **Videos**: grid of all published videos
- **Category**: videos in one category
- **Playlist**: playlist player
- **Video**: a single video, chosen from your videos

### Managing Videos, Categories and Playlists
- **Videos**: edit titles, descriptions, thumbnails, tags and recipes; use **Feature**/**Unfeature** and batch processing from the list
- **Categories**: title, description and YouTube tag
- **Playlists**: title, YouTube playlist ID and description

### Import and Export
Each list screen has **Export** (XML) and **Import** buttons. Video exports include the category alias and YouTube playlist ID, so imports on another site link videos to the matching local categories and playlists. Each import runs in a single transaction.

### Bulk-adding Channel Videos to a Playlist
`bulk_add_to_playlist.php` is a developer CLI tool in this repository; it is not installed with the component. See `docs/BULK_PLAYLIST_GUIDE.md`.

## Database Structure

| Table | Contents |
|---|---|
| `#__youtubevideos_featured` | All videos (synced or added manually), despite the historical name |
| `#__youtubevideos_categories` | Video categories |
| `#__youtubevideos_playlists` | Playlists |
| `#__youtubevideos_statistics` | Views, likes and comments per YouTube video, refreshed by sync |
| `#__youtubevideos_tags` | Tags |
| `#__youtubevideos_video_tag_map` | Video-to-tag links |
| `#__youtubevideos_oauth_tokens` | OAuth tokens for the connected Google account |

## Development
- `./build.sh` builds the component, module, plugin and package zips (it also regenerates the `.min.js` files from their sources).
- Release steps: bump the versions in `youtubevideos.xml` and `pkg_youtubevideos.xml`, add a `docs/CHANGELOG.md` entry, add `admin/sql/updates/mysql/<version>.sql` for schema changes (and mirror them in `install.mysql.sql`), then build.

## Licence

GNU General Public License version 2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## Credits

**Author**: Allan Schweitz  
**Copyright**: © 2024 Brahma Kumaris World Spiritual University  
**Organisation**: Brahma Kumaris World Spiritual University

**Made with ❤️ for the Joomla Community**

