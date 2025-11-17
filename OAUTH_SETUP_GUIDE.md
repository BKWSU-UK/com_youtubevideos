# OAuth 2.0 Setup Guide for YouTube Videos Component

This guide will help you configure OAuth 2.0 authentication to enable syncing of unlisted and private YouTube videos.

## Why OAuth 2.0?

With a simple API key, YouTube only returns **public videos**. To access unlisted and private videos from your channel, you need to authenticate as the channel owner using OAuth 2.0.

## Benefits of OAuth

- ✅ Access to **unlisted videos**
- ✅ Access to **private videos** (if needed)
- ✅ Automatic token refresh
- ✅ Secure authentication
- ✅ Full channel access

## Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Create Project** or select an existing project
3. Give your project a name (e.g., "YouTube Videos Sync")
4. Click **Create**

## Step 2: Enable YouTube Data API v3

1. In the Google Cloud Console, go to **APIs & Services** → **Library**
2. Search for "YouTube Data API v3"
3. Click on it and press **Enable**

## Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **+ CREATE CREDENTIALS** → **OAuth client ID**
3. If prompted, configure the OAuth consent screen:
   - Choose **External** user type
   - Fill in the required fields:
     - App name: "YouTube Videos Component"
     - User support email: your email
     - Developer contact: your email
   - Click **Save and Continue**
   - Skip the Scopes step (click **Save and Continue**)
   - Add yourself as a test user
   - Click **Save and Continue**

4. Create the OAuth Client ID:
   - Application type: **Web application**
   - Name: "Joomla YouTube Sync"
   - Authorized redirect URIs: `https://yoursite.com/administrator/index.php?option=com_youtubevideos&task=oauth.callback`
     (Replace `yoursite.com` with your actual domain)
   - Click **Create**

5. **Copy your Client ID and Client Secret** - you'll need these!

## Step 4: Configure Component in Joomla

1. Log in to your Joomla administrator panel
2. Go to **Components** → **YouTube Videos** → **Options**
3. Navigate to the **OAuth 2.0 Settings** tab
4. Configure the following:
   - **Enable OAuth**: Yes
   - **OAuth Client ID**: Paste your Client ID from Step 3
   - **OAuth Client Secret**: Paste your Client Secret from Step 3
   - **OAuth Redirect URI**: This is auto-filled (copy this to Google Cloud Console if different)
5. Click **Save & Close**

## Step 5: Connect to Google

1. Go to **Components** → **YouTube Videos** (Dashboard)
2. Click the **Connect with Google** button in the toolbar
3. You'll be redirected to Google's authorization page
4. Sign in with the Google account that owns the YouTube channel
5. Review and accept the permissions
6. You'll be redirected back to Joomla

You should see a success message: "Successfully connected to Google! You can now sync unlisted videos."

## Step 6: Verify Connection

On the Dashboard, check the **System Information** panel:
- **OAuth Status** should show **Connected** (green badge)
- You should see: "Can access unlisted videos"

## Step 7: Sync Videos

1. Click the **Sync Videos** button in the toolbar
2. The component will now fetch **all videos** from your channel, including:
   - Public videos
   - Unlisted videos
   - Private videos (if configured)

## Troubleshooting

### "OAuth is not enabled"
- Make sure OAuth is enabled in Component Options
- Save the configuration

### "OAuth Client ID is not configured"
- Enter your Client ID in Component Options
- Double-check you copied it correctly from Google Cloud Console

### "Invalid redirect URI" error from Google
- Make sure the redirect URI in Google Cloud Console exactly matches the one shown in Joomla
- Check for trailing slashes or http vs https
- The redirect URI should be:
  `https://yoursite.com/administrator/index.php?option=com_youtubevideos&task=oauth.callback`

### "Access blocked: This app's request is invalid"
- Make sure you've added yourself as a test user in the OAuth consent screen
- If your app is in "Testing" mode, only test users can connect

### Token Expired
- Don't worry! The component automatically refreshes expired tokens
- If automatic refresh fails, just disconnect and reconnect

## Security Notes

- OAuth tokens are stored securely in the database per user
- Each administrator can connect their own Google account
- Tokens are automatically refreshed before expiration
- The Client Secret is stored encrypted in Joomla's configuration

## Disconnecting OAuth

To disconnect your Google account:
1. Go to the Dashboard
2. Click **Disconnect OAuth** in the toolbar
3. Your OAuth tokens will be removed from the database
4. You can reconnect anytime by clicking **Connect with Google** again

## Publishing Your App (Optional)

If you want to allow other users to connect:
1. Go to Google Cloud Console → OAuth consent screen
2. Click **Publish App**
3. Submit for verification if needed
4. Once verified, any Google account can connect

## Support

For issues or questions:
- Check Joomla logs: System → Maintenance → System Information
- Enable debug mode in Joomla Global Configuration
- Check the component logs for OAuth-related errors

---

**Version:** 1.0.2  
**Last Updated:** November 2024



