#!/bin/bash

# Build script for the YouTube Videos package (component + modules + plugin)
#
# Usage: ./build.sh
#
# Produces in the repository root:
#   com_youtubevideos.zip, mod_youtubevideos.zip, mod_youtube_single.zip,
#   plg_system_youtubevideos.zip and pkg_youtubevideos.zip (recommended install).
#
# Notes:
#   - media/js/*.min.js files are regenerated from their non-minified sources
#     before packaging (they are plain copies; there is no minifier).
#   - bulk_add_to_playlist.php is a developer CLI tool and is not packaged.

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

COMPONENT_FILE="com_youtubevideos.zip"
MODULE_VIDEOS_FILE="mod_youtubevideos.zip"
MODULE_SINGLE_FILE="mod_youtube_single.zip"
PLUGIN_SYSTEM_FILE="plg_system_youtubevideos.zip"
PACKAGE_FILE="pkg_youtubevideos.zip"
PACKAGES_DIR="packages"

EXCLUDES=("*.git*" "*.DS_Store" "*__MACOSX*" "*/.*")

# Zip the given paths into an archive, failing on any zip warning or error.
# Usage: make_zip <archive> <path>...
make_zip() {
    local archive="$1"
    shift

    rm -f "$archive"

    local output
    if ! output=$(zip -r "$archive" "$@" -x "${EXCLUDES[@]}" 2>&1); then
        echo "$output" >&2
        echo "✗ Failed to create $archive" >&2
        exit 1
    fi

    if grep -qi "warning" <<< "$output"; then
        echo "$output" >&2
        echo "✗ zip reported warnings while creating $archive" >&2
        exit 1
    fi
}

# Fail early if a required path is missing.
require_paths() {
    local path
    for path in "$@"; do
        if [ ! -e "$path" ]; then
            echo "✗ Required path not found: $path" >&2
            exit 1
        fi
    done
}

# Regenerate every <name>.min.js from <name>.js in the given directory.
regenerate_min_js() {
    local dir="$1"
    local source

    [ -d "$dir" ] || return 0

    shopt -s nullglob
    for source in "$dir"/*.js; do
        case "$source" in
            *.min.js) continue ;;
        esac
        cp "$source" "${source%.js}.min.js"
    done
    shopt -u nullglob
}

echo "========================================="
echo "Building YouTube Videos Package"
echo "========================================="
echo ""

trap 'rm -rf "$SCRIPT_DIR/$PACKAGES_DIR"' EXIT

# Step 1: Component
echo "Step 1: Building Component..."
require_paths youtubevideos.xml script.php admin site media site/layouts
regenerate_min_js media/js
make_zip "$COMPONENT_FILE" youtubevideos.xml script.php admin site media
echo "✓ Component packaged: $COMPONENT_FILE"
ls -lh "$COMPONENT_FILE"

# Step 2: mod_youtubevideos (grid module)
echo ""
echo "Step 2: Building YouTube Videos Grid Module..."
require_paths modules/mod_youtubevideos/mod_youtubevideos.xml
regenerate_min_js modules/mod_youtubevideos/media/js
(cd modules/mod_youtubevideos && make_zip "../../$MODULE_VIDEOS_FILE" .)
echo "✓ Module packaged: $MODULE_VIDEOS_FILE"
ls -lh "$MODULE_VIDEOS_FILE"

# Step 3: mod_youtube_single
echo ""
echo "Step 3: Building YouTube Single Video Module..."
require_paths modules/mod_youtube_single/mod_youtube_single.xml
regenerate_min_js modules/mod_youtube_single/media/js
(cd modules/mod_youtube_single && make_zip "../../$MODULE_SINGLE_FILE" .)
echo "✓ Module packaged: $MODULE_SINGLE_FILE"
ls -lh "$MODULE_SINGLE_FILE"

# Step 4: plg_system_youtubevideos
echo ""
echo "Step 4: Building System Plugin..."
require_paths plugins/system/youtubevideos/youtubevideos.xml
(cd plugins/system/youtubevideos && make_zip "../../../$PLUGIN_SYSTEM_FILE" .)
echo "✓ Plugin packaged: $PLUGIN_SYSTEM_FILE"
ls -lh "$PLUGIN_SYSTEM_FILE"

# Step 5: Package
echo ""
echo "Step 5: Creating Package..."
require_paths pkg_youtubevideos.xml pkg_script.php language
rm -rf "$PACKAGES_DIR"
mkdir -p "$PACKAGES_DIR"
cp "$COMPONENT_FILE" "$MODULE_VIDEOS_FILE" "$MODULE_SINGLE_FILE" "$PLUGIN_SYSTEM_FILE" "$PACKAGES_DIR/"
make_zip "$PACKAGE_FILE" pkg_youtubevideos.xml pkg_script.php language "$PACKAGES_DIR"
echo "✓ Package created: $PACKAGE_FILE"
ls -lh "$PACKAGE_FILE"

echo ""
echo "========================================="
echo "Build Complete!"
echo "========================================="
echo ""
echo "Files created:"
echo "  1. $COMPONENT_FILE - Standalone component"
echo "  2. $MODULE_VIDEOS_FILE - YouTube Videos Grid module"
echo "  3. $MODULE_SINGLE_FILE - YouTube Single Video module"
echo "  4. $PLUGIN_SYSTEM_FILE - System Plugin"
echo "  5. $PACKAGE_FILE - Complete package (component + all modules + plugin)"
echo ""
echo "Recommended installation: Use $PACKAGE_FILE"
echo ""
