# YouTube Videos Package - Installation Guide

## Package Contents

This package includes:
- **Component**: YouTube Videos Component (`com_youtubevideos`)
- **Module**: YouTube Videos Grid Module (`mod_youtubevideos`) - Display videos from a playlist in a grid
- **Module**: YouTube Single Video Module (`mod_youtube_single`) - Display a single selected video

## Installation Options

You have three installation options:

### Option 1: Complete Package (Recommended)

Install everything in one go using the package installer:

**File**: `pkg_youtubevideos.zip`

1. Go to **Extensions → Manage → Install**
2. Upload `pkg_youtubevideos.zip`
3. Click **Install**

This will automatically install:
- The YouTube Videos Component
- The YouTube Videos Grid Module
- The YouTube Single Video Module

### Option 2: Component Only

Install just the component:

**File**: `com_youtubevideos.zip`

1. Go to **Extensions → Manage → Install**
2. Upload `com_youtubevideos.zip`
3. Click **Install**

Note: This does NOT include the modules. Use Option 1 for complete installation.

### Option 3: Modules Only

Install modules individually (requires component to be installed first):

**Files**: 
- `mod_youtubevideos.zip` - Grid module for displaying playlist videos
- `mod_youtube_single.zip` - Single video module

1. Ensure the component is already installed
2. Go to **Extensions → Manage → Install**
3. Upload the module zip file(s)
4. Click **Install**

## System Requirements

- **Joomla**: 5.0 or higher
- **PHP**: 8.1 or higher
- **Database**: MariaDB/MySQL

## Post-Installation Steps

### 1. Configure the Component

1. Navigate to **Components → YouTube Videos → Options**
2. Add your YouTube Data API v3 credentials
3. Configure cache settings and other options

### 2. Set Up OAuth (Optional)

If you need OAuth authentication for accessing private playlists:

1. Follow the instructions in `OAUTH_SETUP_GUIDE.md`
2. Set up your Google OAuth credentials
3. Authorise the application

### 3. Sync Your Videos

1. Go to **Components → YouTube Videos → Dashboard**
2. Click **Sync Videos** to import videos from your YouTube channel/playlist
3. Wait for the synchronisation to complete

### 4. Configure Modules

**YouTube Videos Grid Module** (mod_youtubevideos):
1. Navigate to **Content → Site Modules**
2. Create new module: **YouTube Videos**
3. Configure settings:
   - Select a playlist
   - Choose grid layout (2, 3, 4, or 6 columns)
   - Set maximum videos to display
   - Configure ordering and display options
   - Assign to module position
4. Save and enable the module

**YouTube Single Video Module** (mod_youtube_single):
1. Navigate to **Content → Site Modules**
2. Create new module: **YouTube Single Video**
3. Configure settings:
   - Search and select a video
   - Choose display mode (Embedded, Card, or Thumbnail)
   - Set visibility options
   - Assign to module position
4. Save and enable the module

## What Gets Installed

### Component (`com_youtubevideos`)

**Administrator**:
- Dashboard with statistics
- Video management (CRUD)
- Category management
- Playlist management
- Import/Export functionality
- OAuth integration
- Configuration options

**Site**:
- Video list view with filtering
- Single video view
- Category view
- Playlist view with player
- SEO-optimised pages with structured data

**Media**:
- CSS stylesheets
- JavaScript for video player
- Asset management

### Module: YouTube Videos Grid (`mod_youtubevideos`)

**Features**:
- Display videos from any playlist in a responsive grid
- Flexible layouts: 2, 3, 4, or 6 columns
- Modal video player with YouTube integration
- Multiple ordering options
- Show/hide title, description, duration
- Bootstrap 5 responsive design
- Cache support

### Module: YouTube Single Video (`mod_youtube_single`)

**Features**:
- Search-as-you-type video selector (admin)
- Three display modes (Embedded, Card, Thumbnail)
- Customisable player options
- Content visibility controls
- Bootstrap 5 responsive design
- Cache support

## Updating

To update to a newer version:

1. Download the latest package
2. Go to **Extensions → Manage → Install**
3. Upload the new package file
4. Joomla will automatically handle the update

**Note**: Your data and settings will be preserved during updates.

## Uninstalling

### To Remove Everything

Uninstall the package:
1. Go to **Extensions → Manage → Manage**
2. Search for "YouTube Videos Package"
3. Select it and click **Uninstall**

This will remove the component and both modules.

### To Remove Individual Extensions

1. Go to **Extensions → Manage → Manage**
2. Search for the specific extension
3. Select it and click **Uninstall**

**Warning**: Uninstalling the component will remove all videos, categories, and playlists from the database. Export your data first if you want to keep it.

## Troubleshooting

### Installation Fails

**Problem**: Package installation fails with version error  
**Solution**: Ensure you have Joomla 5.0+ and PHP 8.1+

**Problem**: "Component not found" error when installing module  
**Solution**: Install the component first (use Option 1 - Complete Package)

### Module Not Showing

**Problem**: Module doesn't appear on the site  
**Solution**: 
- Check module is published
- Verify module position is correct
- Ensure module is assigned to correct menu items
- Confirm a video is selected in module settings

### Video Search Not Working

**Problem**: AJAX search returns no results  
**Solution**:
- Verify component is properly installed
- Check that videos are published in the database
- Ensure JavaScript is not blocked by browser
- Clear Joomla and browser cache

## Support

For additional help:
- Email: allan@bkconnect.net
- Documentation: See `README.md`, `modules/mod_youtubevideos/README.md`, and `modules/mod_youtube_single/README.md`

## Credits

Developed by Allan Schweitz for Brahma Kumaris World Spiritual University  
Copyright © 2025 BKWSU. All rights reserved.

## License

GNU General Public License version 2 or later

