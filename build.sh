#!/bin/bash
#
# Build script for Meta Override plugin
# Creates a distributable ZIP file with vendor dependencies included.
#
# Usage: ./build.sh
#

set -e

PLUGIN_SLUG="meta-override"
PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION=$(grep -m1 "Version:" "$PLUGIN_DIR/meta-override.php" | sed 's/.*Version: *//' | tr -d '[:space:]')
BUILD_DIR=$(mktemp -d)
ZIP_FILE="$PLUGIN_DIR/${PLUGIN_SLUG}-v${VERSION}.zip"

echo "Building ${PLUGIN_SLUG} v${VERSION}..."

# Install production dependencies
echo "Installing Composer dependencies..."
cd "$PLUGIN_DIR"
composer install --no-dev --optimize-autoloader --quiet

# Create plugin directory structure in temp
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Copy files, excluding dev-only files
rsync -a \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='.claude' \
  --exclude='CLAUDE.md' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='*.zip' \
  --exclude='.DS_Store' \
  --exclude='*.log' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='node_modules' \
  --exclude='build.sh' \
  "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_SLUG/"

# Create ZIP
echo "Creating ZIP archive..."
cd "$BUILD_DIR"
zip -rq "$ZIP_FILE" "$PLUGIN_SLUG/"

# Cleanup
rm -rf "$BUILD_DIR"

echo "Build complete: $ZIP_FILE"
