# YouTube Single Video Module (mod_youtube_single)

A Joomla 5 module that displays a single selected YouTube video with search-as-you-type functionality.

## Features

- **Dynamic Video Selection**: Choose between static video selection or dynamic retrieval from menu item parameters
- **Search-as-you-type Video Selector**: Easily find and select videos in the admin interface
- **Menu Item Integration**: Display different videos on different menu items using a single module instance
- **Multiple Display Modes**: 
  - Embedded Player - Full YouTube player with controls
  - Card with Thumbnail - Bootstrap card layout with video thumbnail
  - Thumbnail Only - Simple thumbnail display
- **Customisable Player Options**: Configure width, height, and autoplay settings
- **Flexible Content Display**: Show/hide title, description, and links
- **Responsive Design**: Uses Bootstrap 5 for mobile-friendly display
- **Access Control**: Respects Joomla access levels
- **Multi-language Support**: Full language filtering and translations

## Installation

### Via Component Package
The module is included in the main YouTube Videos Component package (`com_youtube.zip`). When you install or update the component, the module will be automatically installed.

### Standalone Installation
You can also install the module separately:

1. Download `mod_youtube_single.zip`
2. Go to **Extensions → Manage → Install**
3. Upload the zip file
4. Click **Install**

## Configuration

### Basic Settings

1. Navigate to **Content → Site Modules**
2. Click **New** and select **YouTube Single Video**
3. Configure the following settings:

#### Video Source
Choose how the module should determine which video to display:

- **Module Configuration**: Select a specific video in the module settings (default)
- **Menu Item Parameter**: Retrieve the video ID from the active menu item's parameters

##### Module Configuration Option
- Type to search for videos by title or video ID
- The search updates in real-time as you type
- Click on a video from the search results to select it
- Use the **Clear** button to remove the selection

##### Menu Item Parameter Option
- Specify the parameter name that contains the video ID (default: `id`)
- For YouTube Videos component menu items (Single Video view), use `id`
- For custom menu item parameters, specify the parameter name as defined in the menu item configuration
- The module will read this parameter from the active menu item
- This allows different menu items to display different videos using the same module instance

#### Display Mode
Choose how the video should be displayed:

- **Embedded Player**: Shows the full YouTube player with controls
  - Configure player width (default: 100%)
  - Configure player height (default: 315px)
  - Enable/disable autoplay
  
- **Card with Thumbnail**: Displays a Bootstrap card with thumbnail
  - Shows play icon overlay
  - Card format with optional description
  
- **Thumbnail Only**: Simple thumbnail image
  - Minimal display with optional title

#### Content Options

- **Show Title**: Display the video title
- **Show Description**: Display the video description
- **Description Length**: Maximum number of characters (50-1000)
- **Show Link**: Make the video clickable (Card and Thumbnail modes only)

### Advanced Settings

- **Layout**: Choose alternative module layouts
- **Module Class Suffix**: Add custom CSS classes
- **Caching**: Enable/disable module caching
- **Cache Time**: Cache duration in seconds (default: 900)

### Module Position

Assign the module to any module position on your site:
- Choose from template positions (e.g., sidebar-left, sidebar-right, footer)
- Select which pages to display the module on
- Set module ordering

## Usage Examples

### Example 1: Featured Video in Sidebar

Display a featured video in your sidebar with title and description:

1. Select your video using the search function
2. Display Mode: **Card with Thumbnail**
3. Show Title: **Yes**
4. Show Description: **Yes**
5. Description Length: **150**
6. Position: **sidebar-right**

### Example 2: Embedded Video on Homepage

Show a video player on your homepage:

1. Select your video
2. Display Mode: **Embedded Player**
3. Player Width: **100%**
4. Player Height: **480**
5. Autoplay: **No**
6. Show Title: **Yes**
7. Show Description: **No**
8. Position: **home-bottom**
9. Menu Assignment: **Only on selected pages** → Select your homepage

### Example 3: Simple Thumbnail

Display just a video thumbnail with a link:

1. Select your video
2. Display Mode: **Thumbnail Only**
3. Show Title: **Yes**
4. Show Description: **No**
5. Show Link: **Yes**
6. Position: **footer**

### Example 4: Dynamic Video from Menu Item

Display different videos on different pages using a single module:

**Method A: Using YouTube Videos Component Menu Items**
1. Create menu items of type **YouTube Videos → Single Video**
2. Select a different video in each menu item
3. In the module settings:
   - Video Source: **Menu Item Parameter**
   - Menu Parameter Name: **id** (default)
   - Display Mode: **Embedded Player**
   - Show Title: **Yes**
4. Assign the module to **All Pages** or **Selected Pages**
5. Each page will display the video selected in its menu item

**Method B: Using Custom Menu Parameters**
1. Create menu items and add a custom parameter (e.g., `featured_video`) to each menu item
2. In the module settings:
   - Video Source: **Menu Item Parameter**
   - Menu Parameter Name: **featured_video**
   - Display Mode: **Card with Thumbnail**
   - Show Title: **Yes**
3. Assign the module to specific pages
4. Each page will display the video specified in its custom parameter

## Technical Details

### File Structure

```
mod_youtube_single/
├── mod_youtube_single.xml       # Module manifest
├── mod_youtube_single.php       # Module entry point
├── README.md                     # This file
├── src/
│   ├── Helper/
│   │   └── YoutubeSingleHelper.php    # Helper class for fetching videos
│   └── Field/
│       └── YoutubevideoField.php      # Custom form field for video selection
├── tmpl/
│   └── default.php               # Module template
├── media/
│   ├── js/
│   │   └── video-selector.js     # AJAX search functionality
│   └── css/
│       └── mod_youtube_single.css # Module stylesheet
└── language/
    └── en-GB/
        ├── mod_youtube_single.ini      # Language strings
        └── mod_youtube_single.sys.ini  # System language strings
```

### Requirements

- Joomla 5.0+
- PHP 8.1+
- YouTube Videos Component (com_youtubevideos) installed
- At least one published video in the component

### How It Works

1. **Video Selection**: The custom form field uses AJAX to search videos in real-time
2. **Video Fetching**: The helper class retrieves the selected video from the database
3. **Access Control**: Only videos accessible to the current user are displayed
4. **Language Filtering**: Videos are filtered by the current site language
5. **Caching**: Module output can be cached for improved performance

### AJAX Search

The module includes a custom form field that implements search-as-you-type:

- Searches are debounced (300ms delay) to reduce server load
- CSRF token validation for security
- Results are limited to 20 videos
- Searches both video title and YouTube video ID
- XSS protection on all output

## Troubleshooting

### Video Not Showing

1. Verify the video is published in the component
2. Check access level settings
3. Confirm language filtering matches your site language
4. Clear Joomla cache

### Search Not Working

1. Ensure the component is properly installed
2. Check that JavaScript is enabled in your browser
3. Verify there are published videos in the database
4. Check browser console for JavaScript errors

### Styling Issues

1. Ensure Bootstrap 5 is loaded by your template
2. Add custom CSS via Module Class Suffix
3. Check for CSS conflicts with your template

## Support

For issues, questions, or feature requests:
- Component Repository: See main component documentation
- Email: allan@bkconnect.net

## Credits

Developed by Allan Schweitz for Brahma Kumaris World Spiritual University  
Copyright © 2025 BKWSU. All rights reserved.

## License

GNU General Public License version 2 or later


