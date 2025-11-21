#!/bin/bash

# Build script for com_youtubevideos Joomla component

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Output file
OUTPUT_FILE="com_youtube.zip"

# Remove old zip if exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo "Removed old $OUTPUT_FILE"
fi

# Create the zip file
zip -r "$OUTPUT_FILE" \
    youtubevideos.xml \
    script.php \
    bulk_add_to_playlist.php \
    BULK_PLAYLIST_GUIDE.md \
    admin \
    site \
    media \
    layouts \
    -x "*.git*" "*.DS_Store" "*__MACOSX*" "*/.*"

echo "Component packaged successfully: $OUTPUT_FILE"

# Show the file size
ls -lh "$OUTPUT_FILE"




