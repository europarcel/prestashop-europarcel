#!/bin/bash

# EuroParcel Plugin Build Script
# Creates a clean distribution zip file on the Desktop

# Set variables
MODULE_NAME="europarcel"
VERSION="1.0.2"
DESKTOP_PATH="$HOME/Desktop"
OUTPUT_ZIP="${DESKTOP_PATH}/${MODULE_NAME}.zip"
TEMP_DIR="/tmp/${MODULE_NAME}_build"

# Clean up any previous temp directory
if [ -d "$TEMP_DIR" ]; then
    rm -rf "$TEMP_DIR"
fi

# Create temporary directory structure
mkdir -p "$TEMP_DIR/${MODULE_NAME}"

# Copy essential files
echo "Copying plugin files..."

# Main module files
cp europarcel.php "$TEMP_DIR/${MODULE_NAME}/"
cp config.xml "$TEMP_DIR/${MODULE_NAME}/"
cp logo.png "$TEMP_DIR/${MODULE_NAME}/"
cp index.php "$TEMP_DIR/${MODULE_NAME}/"
cp .htaccess "$TEMP_DIR/${MODULE_NAME}/"

# Controllers
cp -r controllers "$TEMP_DIR/${MODULE_NAME}/"

# Override classes
cp -r override "$TEMP_DIR/${MODULE_NAME}/"

# Views (templates, CSS, JS, images)
cp -r views "$TEMP_DIR/${MODULE_NAME}/"

# Remove any existing zip file
if [ -f "$OUTPUT_ZIP" ]; then
    rm "$OUTPUT_ZIP"
fi

# Create zip file (excluding git and other unnecessary files)
echo "Creating zip archive..."
cd "$TEMP_DIR"
zip -r "$OUTPUT_ZIP" "$MODULE_NAME" -q -x "*/.git/*" "*/.git" "*.DS_Store" "*/.gitignore" "*/build.sh" "*/README.md" "*/LICENSE"

# Clean up temp directory
rm -rf "$TEMP_DIR"

# Check if zip was created successfully
if [ -f "$OUTPUT_ZIP" ]; then
    echo "✓ Build successful!"
    echo "✓ Package created: $OUTPUT_ZIP"
    ls -lh "$OUTPUT_ZIP"
else
    echo "✗ Build failed!"
    exit 1
fi

