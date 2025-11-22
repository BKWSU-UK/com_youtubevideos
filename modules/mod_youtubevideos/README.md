# YouTube Videos Module

A Joomla 5 module for displaying YouTube videos from a selected playlist in a responsive grid layout with modal player support.

## Features

- **Playlist Selection**: Display videos from any published YouTube playlist in your component database
- **Responsive Grid Layout**: Choose between 2, 3, 4, or 6 videos per row
- **Modal Video Player**: Play videos in a modal popup with YouTube player integration
- **Customisable Display**: Toggle visibility of title, description, and duration
- **Automatic Description Retrieval**: Fetches missing descriptions from YouTube API when database description is empty
- **Flexible Ordering**: Sort videos by custom order, date, or title (ascending/descending)
- **Bootstrap 5 Styling**: Clean, modern design using Bootstrap 5 classes and variables
- **Multi-lingual Support**: Fully translatable with language files
- **Caching Support**: Optional caching for improved performance
- **Access Control**: Respects Joomla access levels and language filtering

## Installation

1. Navigate to **System → Install → Extensions** in your Joomla administrator
2. Upload the `mod_youtubevideos.zip` file
3. Click **Install**

## Configuration

After installation, configure the module through **Content → Site Modules**:

### Basic Options

- **Playlist**: Select the YouTube playlist to display videos from (required)
- **Maximum Videos**: Set the maximum number of videos to display (1-50, default: 12)
- **Videos per Row**: Choose layout: 2, 3, 4, or 6 columns (default: 3)
- **Video Ordering**: Choose how videos are sorted:
  - Custom Order (Ascending) - Uses ordering field, then newest first (default)
  - Custom Order (Descending) - Uses ordering field descending, then newest first
  - Newest First - Most recent videos first
  - Oldest First - Oldest videos first
  - Title (A-Z) - Alphabetical by title
  - Title (Z-A) - Reverse alphabetical by title
- **Show Video Title**: Toggle video title display (default: Yes)
- **Show Description**: Toggle video description display (default: Yes)
- **Show Duration**: Toggle video duration badge display (default: Yes)
- **Module Heading**: Optional heading text above the video grid
- **Enable Modal Player**: Play videos in modal popup when clicked (default: Yes)
- **Caching**: Enable/disable caching (default: Yes)
- **Cache Time**: Cache duration in seconds (default: 900)

### Advanced Options

- **Layout**: Select alternative module layout (if available)
- **Module Class Suffix**: Add custom CSS classes
- **Caching**: Additional cache settings

## Usage

1. Create and publish YouTube playlists in the YouTube Videos component
2. Create a new module position or select an existing one
3. Assign the module to the desired menu items
4. Configure the module settings as needed
5. Save and view the module on your site

## Requirements

- Joomla 5.x
- YouTube Videos Component (com_youtubevideos) installed and configured
- Bootstrap 5 (included in Joomla 5)
- Published YouTube playlists in the component database

## File Structure

```
mod_youtubevideos/
├── mod_youtubevideos.xml       # Module manifest
├── mod_youtubevideos.php       # Main module entry file
├── src/
│   └── Helper/
│       └── YoutubeHelper.php   # Helper class for data retrieval
├── tmpl/
│   └── default.php             # Default template layout
├── language/
│   └── en-GB/
│       ├── mod_youtubevideos.ini     # Language file
│       └── mod_youtubevideos.sys.ini # System language file
└── media/
    └── css/
        └── module.css          # Module styles
```

## Styling

The module uses Bootstrap 5 classes and variables for styling, ensuring consistent appearance with your Joomla site theme. Custom styles are defined in `media/css/module.css` and follow Bootstrap conventions.

### Customisation

You can customise the module appearance by:

1. Adding custom CSS through the **Module Class Suffix** parameter
2. Creating alternative module layouts in your template
3. Overriding the module CSS in your template

## Support

For issues, feature requests, or questions about this module, please contact your development team or refer to the component documentation.

## Version History

- **1.0.0** (November 2025)
  - Initial release
  - Playlist-based video display
  - Responsive grid layout
  - Modal video player
  - Bootstrap 5 styling
  - Multi-lingual support
  - Caching support

## Licence

GNU General Public License version 2 or later

## Copyright

Copyright (C) 2024 BKWSU. All rights reserved.

