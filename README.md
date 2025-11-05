# YouTube Videos Component for Joomla 5

A professional Joomla 5 component for displaying and managing YouTube videos from specific channels and playlists on your website.

## Description

The YouTube Videos Component integrates seamlessly with the YouTube Data API v3 to fetch, display, and manage videos from your YouTube channels and playlists. It provides a comprehensive backend management system and an elegant frontend display with filtering and search capabilities.

## Features

### Frontend Features
- Display videos from YouTube channels or playlists
- Video grid layout with responsive design
- Modal video player using YouTube IFrame API
- Search and filter videos by tags
- Multi-language support
- Configurable videos per page
- Customisable grid columns (2, 3, or 4 columns)
- Optional display of video metadata (views, description, publish date)

### Backend Features
- **Dashboard**: Overview with statistics, popular videos, and system status
- **Featured Videos Management**: CRUD operations with drag-and-drop ordering
- **Categories Management**: Organise videos by categories with YouTube tags
- **Playlists Management**: Import and manage YouTube playlists
- **Statistics Tracking**: Track video views and likes
- **Caching System**: Built-in caching to reduce API calls
- **ACL Integration**: Granular permission control
- **Multi-language Support**: Full internationalisation

## Requirements

- **Joomla**: 5.0 or higher
- **PHP**: 8.3 or higher
- **MySQL**: 5.7 or higher
- **YouTube Data API v3 Key**: Required for API access

## Installation

### Step 1: Get a YouTube API Key

1. Visit the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the YouTube Data API v3
4. Create credentials (API Key)
5. Restrict the API key to YouTube Data API v3 (recommended)

### Step 2: Install the Component

1. Download the component package (`com_youtube.zip`)
2. Log in to your Joomla administrator panel
3. Navigate to **System** → **Install** → **Extensions**
4. Upload and install the package
5. The component will automatically create the necessary database tables

### Step 3: Configure the Component

1. Navigate to **Components** → **YouTube Videos**
2. Click the **Options** button in the toolbar
3. Enter your YouTube API key
4. Enter your YouTube Channel ID (find it at https://www.youtube.com/account_advanced)
5. Optionally enter a Playlist ID
6. Configure display and cache settings
7. Save the configuration

## Configuration Options

### Basic Settings
- **YouTube API Key**: Your Google YouTube Data API v3 key (required)
- **Channel ID**: YouTube channel ID to fetch videos from (required)
- **Playlist ID**: YouTube playlist ID (optional)

### Display Settings
- **Videos per Page**: Number of videos to display per page (default: 12)
- **Grid Columns**: Number of columns in desktop view (2, 3, or 4)
- **Show Description**: Display video descriptions
- **Show Published Date**: Display video publication dates
- **Show View Count**: Display view counts

### Cache Settings
- **Enable Cache**: Enable/disable API response caching
- **Cache Time**: Duration in minutes to cache YouTube API responses (default: 60)

## Usage

### Creating Menu Items

#### Videos List
1. Navigate to **Menus** → Select your menu
2. Click **New** to create a menu item
3. Select **YouTube Videos** → **Videos**
4. Configure the menu item parameters
5. Save

#### Single Video
1. Follow the same process
2. Select **YouTube Videos** → **Video**
3. Specify the video ID in the parameters

#### Category View
1. Follow the same process
2. Select **YouTube Videos** → **Category**
3. Select the category to display

### Managing Featured Videos

1. Navigate to **Components** → **YouTube Videos** → **Featured Videos**
2. Click **New** to add a video
3. Enter the video details:
   - Title
   - YouTube Video ID
   - Description (optional)
   - Category (optional)
   - Custom thumbnail (optional)
4. Set as **Featured** and **Published**
5. Save

### Managing Categories

1. Navigate to **Components** → **YouTube Videos** → **Categories**
2. Click **New** to create a category
3. Enter category details:
   - Title
   - Description
   - YouTube Tag (for filtering)
4. Publish the category
5. Save

### Managing Playlists

1. Navigate to **Components** → **YouTube Videos** → **Playlists**
2. Click **New** to add a playlist
3. Enter the YouTube Playlist ID
4. Publish the playlist
5. Save

## Database Structure

### Tables

#### `#__youtubevideos_featured`
Stores featured/curated videos with custom metadata.

#### `#__youtubevideos_categories`
Stores video categories with YouTube tags for filtering.

#### `#__youtubevideos_playlists`
Stores YouTube playlist information.

#### `#__youtubevideos_statistics`
Tracks video statistics (views, likes) from YouTube API.

#### `#__youtubevideos_tags`
Stores custom tags for video organisation.

#### `#__youtubevideos_video_tag_map`
Many-to-many relationship between videos and tags.

## File Structure

```
com_youtubevideos/
├── admin/                          # Backend files
│   ├── access.xml                  # ACL configuration
│   ├── config.xml                  # Component configuration
│   ├── language/                   # Backend language files
│   │   └── en-GB/
│   │       ├── com_youtubevideos.ini
│   │       └── com_youtubevideos.sys.ini
│   ├── layouts/                    # Backend layouts
│   │   └── components/youtubevideos/admin/dashboard/
│   ├── services/
│   │   └── provider.php            # Dependency injection
│   ├── sql/                        # Database scripts
│   │   ├── install.mysql.sql
│   │   ├── uninstall.mysql.sql
│   │   └── updates/mysql/
│   ├── src/
│   │   ├── Controller/             # Backend controllers
│   │   ├── Extension/              # Component extension class
│   │   ├── Model/                  # Backend models
│   │   └── View/                   # Backend views
│   └── tmpl/                       # Backend templates
├── media/                          # Media files
│   ├── css/
│   │   └── component.css
│   ├── js/
│   │   └── youtube-player.js
│   └── joomla.asset.json           # Web Asset configuration
├── site/                           # Frontend files
│   ├── forms/
│   │   └── filter_videos.xml
│   ├── language/                   # Frontend language files
│   │   └── en-GB/
│   │       └── com_youtubevideos.ini
│   ├── src/
│   │   ├── Controller/             # Frontend controllers
│   │   ├── Helper/
│   │   │   └── YoutubeHelper.php   # YouTube API helper
│   │   ├── Model/                  # Frontend models
│   │   └── View/                   # Frontend views
│   └── tmpl/                       # Frontend templates
├── script.php                      # Installation script
└── youtubevideos.xml              # Component manifest
```

## Development

### Technologies Used
- **PHP 8.3+**: Modern PHP with type declarations
- **Joomla 5 Framework**: MVC architecture
- **YouTube Data API v3**: Video data retrieval
- **Bootstrap 5**: Frontend styling (via Joomla)
- **YouTube IFrame API**: Video playback

### Coding Standards
- PSR-12 coding standard
- Joomla coding standards
- Strict type declarations
- Comprehensive PHPDoc blocks
- Dependency injection

### Error Handling
- Configuration validation
- API error handling
- HTTP error checking
- JSON parsing validation
- Comprehensive logging using Joomla's Log API

### Caching Strategy
The component implements intelligent caching to minimize YouTube API quota usage:
- Configurable cache duration
- Separate cache for channels and playlists
- Cache keys based on search parameters
- Automatic fallback on cache failures

## Troubleshooting

### Common Issues

#### "YouTube API key is not configured"
**Solution**: Navigate to component options and enter your YouTube Data API v3 key.

#### "No videos found"
**Possible causes**:
- Incorrect Channel ID or Playlist ID
- API quota exceeded
- Channel/Playlist is empty or private

**Solution**: Check configuration, verify IDs, and check YouTube API quota in Google Cloud Console.

#### "Call to a member function on null"
**Solution**: Clear Joomla cache and ensure all required parameters are configured.

#### Videos not updating
**Solution**: Clear the component cache or reduce cache time in component options.

### Debugging

Enable Joomla debugging to see detailed error messages:
1. Navigate to **System** → **Global Configuration**
2. Go to the **System** tab
3. Set **Debug System** to **Yes**
4. Check error messages in the browser console and Joomla logs

### Logs

Component logs are stored in Joomla's log directory:
- Location: `[JOOMLA_ROOT]/administrator/logs/`
- File: `com_youtubevideos.php`

## Security

- All database queries use parameter binding to prevent SQL injection
- Input validation on all user inputs
- ACL checks throughout the component
- XSS protection using Joomla's escape methods
- CSRF token verification on forms

## Permissions

The component supports the following permissions:
- **Configure**: Access to component options
- **Access Component**: Access to backend component
- **Create**: Create new items
- **Delete**: Delete items
- **Edit**: Edit items
- **Edit State**: Change published state
- **Edit Own**: Edit own created items

## API Quota Management

YouTube Data API v3 has daily quota limits. To manage quota usage:

1. **Enable Caching**: Set cache time to 60+ minutes
2. **Limit Results**: Don't request more videos than needed
3. **Monitor Usage**: Check quota in Google Cloud Console
4. **Request Increase**: If needed, request quota increase from Google

Default quota: 10,000 units/day
- Search request: ~100 units
- Playlist items: ~1 unit

## Multi-language Support

The component is fully translatable. Language files are located in:
- Backend: `admin/language/[lang-code]/com_youtubevideos.ini`
- Frontend: `site/language/[lang-code]/com_youtubevideos.ini`

To add a new language:
1. Copy the `en-GB` folder
2. Rename to your language code (e.g., `de-DE`)
3. Translate all language strings
4. Install via Joomla's language manager

## Updates

Future updates will be available through:
1. Manual download and installation
2. Joomla Update System (when update server is configured)

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Follow Joomla coding standards
4. Add unit tests for new features
5. Submit a pull request

## Support

For support and questions:
- **Email**: allan@bkconnect.net
- **Website**: https://www.brahmakumaris.org

## Licence

GNU General Public License version 2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## Credits

**Author**: Allan Schweitz  
**Copyright**: © 2024 Brahma Kumaris World Spiritual University  
**Organisation**: Brahma Kumaris World Spiritual University

## Version History

### Version 1.0.0 (November 2024)
- Initial release
- YouTube channel and playlist integration
- Featured videos management
- Categories and playlists management
- Statistics tracking
- Caching system
- Multi-language support
- Responsive design
- Dashboard with analytics

## Roadmap

Planned features for future releases:
- [ ] Video comments integration
- [ ] Live stream support
- [ ] Advanced analytics
- [ ] Video upload functionality
- [ ] Subtitle/caption support
- [ ] Video recommendations
- [ ] Social sharing integration
- [ ] Export functionality
- [ ] CLI commands for maintenance
- [ ] REST API endpoints

---

**Made with ❤️ for the Joomla Community**

