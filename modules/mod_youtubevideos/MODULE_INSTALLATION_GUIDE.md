# YouTube Videos Module - Installation and Usage Guide

## Overview

The YouTube Videos Module (`mod_youtubevideos`) is a Joomla 5 module that displays videos from a selected YouTube playlist in a responsive grid layout. It provides a similar appearance to the component's videos view but without search, pagination, or filters.

## Installation

### Prerequisites

1. Joomla 5.x installed and running
2. YouTube Videos Component (com_youtubevideos) installed and configured
3. At least one published YouTube playlist in the component database

### Installation Steps

1. Navigate to **System → Install → Extensions** in your Joomla administrator panel
2. Click on the **Upload Package File** tab
3. Select or drag the `mod_youtubevideos.zip` file
4. Click **Upload & Install**
5. Wait for the confirmation message: "Installation of the module was successful"

## Configuration

### Creating a Module Instance

1. Navigate to **Content → Site Modules**
2. Click the **New** button
3. Select **YouTube Videos** from the module type list
4. Configure the module settings (see below)
5. Assign the module to a position
6. Assign the module to menu items
7. Click **Save & Close**

### Module Parameters

#### Basic Options

**Playlist** (Required)
- Select the YouTube playlist to display videos from
- Only published playlists from your component database are shown
- This field is required for the module to function

**Maximum Videos**
- Number: 1-50 (Default: 12)
- Maximum number of videos to display from the playlist
- Videos are ordered by their ordering field and creation date

**Videos per Row**
- Options: 2, 3, 4, or 6 (Default: 3)
- Number of video columns in the grid layout
- Automatically responsive on smaller screens

**Video Ordering**
- Options:
  - Custom Order (Ascending) - Default
  - Custom Order (Descending)
  - Newest First
  - Oldest First
  - Title (A-Z)
  - Title (Z-A)
- Controls the sort order of videos displayed
- Custom Order uses the ordering field from the database
- Date ordering uses the video creation date
- Title ordering sorts alphabetically by video title

**Show Video Title**
- Toggle: Yes/No (Default: Yes)
- Display the video title below the thumbnail

**Show Description**
- Toggle: Yes/No (Default: Yes)
- Display a truncated video description (100 characters)

**Show Duration**
- Toggle: Yes/No (Default: Yes)
- Display video duration badge overlay on thumbnail

**Module Heading**
- Text field (Optional)
- Custom heading text displayed above the video grid
- Leave empty for no heading

**Enable Modal Player**
- Toggle: Yes/No (Default: Yes)
- When enabled, clicking videos opens them in a modal popup
- When disabled, videos are not clickable (for display-only purposes)

**Caching**
- Toggle: Yes/No (Default: Yes)
- Enable module output caching for better performance

**Cache Time**
- Number in seconds (Default: 900)
- How long cached output is stored (900 seconds = 15 minutes)

#### Advanced Options

**Layout**
- Select alternative module layouts if available in your template

**Module Class Suffix**
- Add custom CSS classes to the module wrapper
- Useful for template-specific styling

## Features

### Responsive Grid Layout

The module automatically adjusts the number of columns based on screen size:
- **Desktop (>1200px)**: Uses your configured columns
- **Tablet (768-1200px)**: Adjusts to 3 columns for 4/6 column layouts
- **Mobile (<768px)**: 2 columns
- **Small Mobile (<480px)**: 1 column

### Modal Video Player

When enabled, the modal player provides:
- Full-screen video playback using YouTube IFrame API
- Video description display below the player
- Smooth Bootstrap 5 modal transitions
- Keyboard navigation support (Escape to close)

### Bootstrap 5 Integration

The module uses Bootstrap 5:
- Classes and utilities for consistent styling
- CSS custom properties for theme compatibility
- Responsive grid system
- Modal component

### Access Control

The module respects:
- Joomla access levels
- Language filtering
- Published state of videos and playlists

## Styling and Customisation

### Custom CSS

Add custom styles using the **Module Class Suffix** parameter:

```css
/* Example: Add custom spacing */
.custom-video-module {
    padding: 2rem;
    background: #f8f9fa;
}
```

Then set Module Class Suffix to: `custom-video-module`

### Template Override

Create a template override for complete layout control:

1. Copy `/modules/mod_youtubevideos/tmpl/default.php`
2. To: `/templates/YOUR_TEMPLATE/html/mod_youtubevideos/default.php`
3. Customise as needed

### Alternative Layouts

Create alternative layouts in your template:

1. Create: `/templates/YOUR_TEMPLATE/html/mod_youtubevideos/LAYOUT_NAME.php`
2. Select it in module settings under **Advanced → Layout**

## Troubleshooting

### No Videos Displayed

**Issue**: Module shows "No videos found in the selected playlist"

**Solutions**:
1. Verify the playlist has published videos
2. Check video access levels match your user permissions
3. Ensure videos match the current language or are set to "All"
4. Clear Joomla cache: **System → Clear Cache**

### Playlist Dropdown is Empty

**Issue**: No playlists appear in the module configuration

**Solutions**:
1. Ensure the YouTube Videos Component is installed
2. Create and publish at least one playlist in the component
3. Verify database connectivity

### Videos Not Playing in Modal

**Issue**: Modal opens but video doesn't play

**Solutions**:
1. Check browser console for JavaScript errors
2. Ensure the component's JavaScript file is accessible
3. Verify YouTube IFrame API is not blocked by ad blockers
4. Test with "Enable Modal Player" set to Yes

### Styling Issues

**Issue**: Module doesn't match site theme

**Solutions**:
1. Ensure your template includes Bootstrap 5
2. Check for CSS conflicts using browser developer tools
3. Add custom CSS using Module Class Suffix
4. Create a template override with custom markup

## Best Practices

### Performance

1. **Enable Caching**: Set cache time to 900-3600 seconds
2. **Limit Videos**: Don't display more videos than needed
3. **Optimise Images**: Use appropriate thumbnail sizes
4. **Module Positions**: Place in positions that don't slow page load

### User Experience

1. **Grid Layout**: Use 3-4 columns for best readability
2. **Module Heading**: Add descriptive heading for context
3. **Description**: Show descriptions to help users choose videos
4. **Modal Player**: Keep enabled for better viewing experience

### Content Management

1. **Playlist Organisation**: Group related videos in playlists
2. **Regular Updates**: Keep playlists current and relevant
3. **Access Control**: Set appropriate access levels
4. **Language Tags**: Tag videos for multi-lingual sites

## Support

For issues, questions, or feature requests related to this module, please refer to:
- Module README.md
- Module CHANGELOG.md
- Component documentation
- Your development team

## File Locations

After installation, module files are located at:

```
/modules/mod_youtubevideos/          # Module root
/media/mod_youtubevideos/            # Module assets (CSS)
/language/en-GB/mod_youtubevideos.*  # Language files
```

## Version Information

- **Current Version**: 1.0.0
- **Release Date**: November 2025
- **Joomla Version**: 5.x
- **PHP Version**: 8.3+

## Licence

GNU General Public License version 2 or later

## Copyright

Copyright (C) 2024 BKWSU. All rights reserved.

