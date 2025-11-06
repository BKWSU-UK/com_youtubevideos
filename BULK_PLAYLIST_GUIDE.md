# Bulk Add Videos to Playlist - Quick Guide

This script automatically adds all videos from your YouTube channel to a playlist using the YouTube Data API.

## Prerequisites

✅ OAuth must be connected in the Joomla component  
✅ Channel ID must be configured in Component Options  
✅ PHP CLI must be available  
✅ You must have manage permissions on the target playlist

## Step-by-Step Instructions

### 1. Create a New Playlist on YouTube

1. Go to [YouTube Studio](https://studio.youtube.com/)
2. Switch to the channel you manage
3. Click **Playlists** → **New Playlist**
4. Name it (e.g., "All Videos for Sync")
5. Set privacy (Public, Unlisted, or Private)
6. Click **Create**

### 2. Get Your Playlist ID

1. In YouTube Studio, click on your new playlist
2. Look at the URL in your browser:
   ```
   https://studio.youtube.com/playlist/PLaBcDeFgHiJkLmNoPqRsTuV/videos
   ```
3. Copy the part starting with **PL** (in this example: `PLaBcDeFgHiJkLmNoPqRsTuV`)

### 3. Run the Script

From the Joomla root directory, run:

```bash
php components/com_youtubevideos/bulk_add_to_playlist.php PLaBcDeFgHiJkLmNoPqRsTuV
```

Replace `PLaBcDeFgHiJkLmNoPqRsTuV` with your actual playlist ID.

### 4. Wait for Completion

The script will:
- ✓ Verify OAuth credentials
- ✓ Fetch all videos from the channel (including unlisted)
- ✓ Add each video to the playlist
- ✓ Show progress in real-time
- ✓ Display a summary when done

**Note:** For hundreds of videos, this may take 5-10 minutes due to YouTube API rate limits.

### 5. Configure Joomla Component

After the script completes successfully:

1. Go to **Components → YouTube Videos → Options**
2. In **Basic Settings**, enter your Playlist ID (the PL... ID)
3. Click **Save & Close**
4. Click **Sync Videos** in the toolbar

✅ **All your unlisted videos will now sync!**

## Example Output

```
╔════════════════════════════════════════════════════════════╗
║  YouTube Videos - Bulk Add to Playlist Tool               ║
╚════════════════════════════════════════════════════════════╝

Target Playlist ID: PLaBcDeFgHiJkLmNoPqRsTuV

[1/5] Retrieving OAuth credentials...
✓ OAuth credentials found for user ID: 123

[2/5] Checking token validity...
✓ Token is valid

Channel ID: UCuWv3X4mZFj00wRcKnBffcQ

[3/5] Fetching all videos from channel...
Fetching page 1...
  Found 50 videos on this page
Fetching page 2...
  Found 50 videos on this page
Fetching page 3...
  Found 23 videos on this page

✓ Total videos found: 123

[4/5] Adding videos to playlist...
This may take a while...

[100%] Processing video 123/123...

✓ Operation completed!

[5/5] Summary:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total videos processed:  123
Successfully added:      123
Already in playlist:     0
Errors:                  0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ SUCCESS! Your playlist is ready.
  You can now use this playlist ID in the Joomla component:
  PLaBcDeFgHiJkLmNoPqRsTuV

Done!
```

## Troubleshooting

### Error: "No OAuth token found in database"
**Solution:** Connect OAuth through the Joomla component first (Components → YouTube Videos → Dashboard → Connect with Google)

### Error: "Channel ID not configured"
**Solution:** Configure the Channel ID in Component Options → Basic Settings

### Error: "Invalid playlist ID format"
**Solution:** Make sure you're using the playlist ID that starts with **PL**, not **UU**

### Error: "The playlist identified... cannot be found"
**Solution:** 
- Verify the playlist ID is correct
- Make sure you have edit permissions on the playlist
- Ensure the playlist belongs to the same channel you're managing

### Rate Limiting
The script automatically handles YouTube's rate limits with built-in delays. If you see rate limit errors, the script will show them but continue processing.

## Technical Details

- **API Used:** YouTube Data API v3
- **Authentication:** OAuth 2.0 (from Joomla component)
- **Rate Limit:** ~3 requests per second
- **Max Results per Request:** 50 videos
- **Estimated Time:** ~0.35 seconds per video + fetch time

## Support

For issues or questions, check the Joomla system logs at:
**System → Maintenance → System Information**

---

**Version:** 1.0.2  
**Last Updated:** November 2024


