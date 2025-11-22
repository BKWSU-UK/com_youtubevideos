#!/bin/bash

# Build script for YouTube Videos Package (component + modules)

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Output files
COMPONENT_FILE="com_youtubevideos.zip"
MODULE_VIDEOS_FILE="mod_youtubevideos.zip"
MODULE_SINGLE_FILE="mod_youtube_single.zip"
PACKAGE_FILE="pkg_youtubevideos.zip"

echo "========================================="
echo "Building YouTube Videos Package"
echo "========================================="
echo ""

# Step 1: Build the component
echo "Step 1: Building Component..."
if [ -f "$COMPONENT_FILE" ]; then
    rm "$COMPONENT_FILE"
fi

zip -r "$COMPONENT_FILE" \
    youtubevideos.xml \
    script.php \
    bulk_add_to_playlist.php \
    BULK_PLAYLIST_GUIDE.md \
    admin \
    site \
    media \
    layouts \
    -x "*.git*" "*.DS_Store" "*__MACOSX*" "*/.*" \
    > /dev/null

echo "✓ Component packaged: $COMPONENT_FILE"
ls -lh "$COMPONENT_FILE"

# Step 2: Build mod_youtubevideos (grid module)
echo ""
echo "Step 2: Building YouTube Videos Grid Module..."
if [ -d "modules/mod_youtubevideos" ]; then
    if [ -f "$MODULE_VIDEOS_FILE" ]; then
        rm "$MODULE_VIDEOS_FILE"
    fi
    
    cd modules/mod_youtubevideos
    zip -r "../../$MODULE_VIDEOS_FILE" \
        . \
        -x "*.git*" "*.DS_Store" "*__MACOSX*" "*/.*" \
        > /dev/null
    cd "$SCRIPT_DIR"
    
    echo "✓ Module packaged: $MODULE_VIDEOS_FILE"
    ls -lh "$MODULE_VIDEOS_FILE"
else
    echo "✗ Module directory not found: modules/mod_youtubevideos"
    exit 1
fi

# Step 3: Build mod_youtube_single
echo ""
echo "Step 3: Building YouTube Single Video Module..."
if [ -d "modules/mod_youtube_single" ]; then
    if [ -f "$MODULE_SINGLE_FILE" ]; then
        rm "$MODULE_SINGLE_FILE"
    fi
    
    cd modules/mod_youtube_single
    zip -r "../../$MODULE_SINGLE_FILE" \
        . \
        -x "*.git*" "*.DS_Store" "*__MACOSX*" "*/.*" \
        > /dev/null
    cd "$SCRIPT_DIR"
    
    echo "✓ Module packaged: $MODULE_SINGLE_FILE"
    ls -lh "$MODULE_SINGLE_FILE"
else
    echo "✗ Module directory not found: modules/mod_youtube_single"
    exit 1
fi

# Step 4: Create package structure
echo ""
echo "Step 4: Creating Package..."

# Create temporary packages directory
PACKAGES_DIR="packages"
if [ -d "$PACKAGES_DIR" ]; then
    rm -rf "$PACKAGES_DIR"
fi
mkdir -p "$PACKAGES_DIR"

# Copy component and module zips to packages directory
cp "$COMPONENT_FILE" "$PACKAGES_DIR/"
cp "$MODULE_VIDEOS_FILE" "$PACKAGES_DIR/"
cp "$MODULE_SINGLE_FILE" "$PACKAGES_DIR/"

# Remove old package if exists
if [ -f "$PACKAGE_FILE" ]; then
    rm "$PACKAGE_FILE"
fi

# Create the package zip
zip -r "$PACKAGE_FILE" \
    pkg_youtubevideos.xml \
    pkg_script.php \
    language \
    packages \
    -x "*.git*" "*.DS_Store" "*__MACOSX*" "*/.*" \
    > /dev/null

# Clean up temporary packages directory
rm -rf "$PACKAGES_DIR"

echo "✓ Package created: $PACKAGE_FILE"
ls -lh "$PACKAGE_FILE"

# Summary
echo ""
echo "========================================="
echo "Build Complete!"
echo "========================================="
echo ""
echo "Files created:"
echo "  1. $COMPONENT_FILE - Standalone component"
echo "  2. $MODULE_VIDEOS_FILE - YouTube Videos Grid module"
echo "  3. $MODULE_SINGLE_FILE - YouTube Single Video module"
echo "  4. $PACKAGE_FILE - Complete package (component + all modules)"
echo ""
echo "Recommended installation: Use $PACKAGE_FILE"
echo ""




