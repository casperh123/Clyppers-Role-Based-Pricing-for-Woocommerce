#!/usr/bin/env bash
set -euo pipefail

PLUGIN_NAME="clyppers-role-based-pricing-for-woocommerce"
DIST_DIR="dist"
BUILD_DIR="${DIST_DIR}/${PLUGIN_NAME}"
VERSION=$(grep -oP 'Version:\s*\K[\d.]+' "${PLUGIN_NAME}.php")
STABLE_TAG=$(grep -oP 'Stable tag:\s*\K[\d.]+' readme.txt)
BUILD_FILE="${PLUGIN_NAME}-${VERSION}.zip"

# Header version and readme stable tag must match
if [[ "$VERSION" != "$STABLE_TAG" ]]; then
    echo "Version mismatch: plugin header=${VERSION}, readme Stable tag=${STABLE_TAG}" >&2
    exit 1
fi

# Use local Composer if installed, otherwise the official Docker image
composer() {
    if type -P composer >/dev/null 2>&1; then
        command composer "$@"
    else
        docker run --rm --user "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 "$@"
    fi
}

# Restore dev dependencies when the script exits, even on failure
trap 'echo "Restoring dev PHP dependencies..."; composer install --no-interaction --quiet' EXIT

# Clean previous builds
echo "Cleaning..."
rm -rf "$BUILD_DIR"
rm -f "${DIST_DIR}/${BUILD_FILE}"

# Build JS/CSS
echo "Building assets..."
npm ci
npm run build

# Production autoloader only (no PHPUnit, no stubs)
echo "Installing production PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet

mkdir -p "$BUILD_DIR"

echo "Copying plugin files..."
cp "${PLUGIN_NAME}.php" readme.txt changelog.txt "$BUILD_DIR/"
cp -R includes assets build vendor "$BUILD_DIR/"

# Human-readable source for the compiled JS (wp.org guideline 4)
cp -R src "$BUILD_DIR/"
cp package.json package-lock.json tsconfig.json webpack.config.js "$BUILD_DIR/"

# Create ZIP inside dist
echo "Creating ZIP..."
( cd "$DIST_DIR" && zip -rq "$BUILD_FILE" "$PLUGIN_NAME" )

# Remove temporary build folder
rm -rf "$BUILD_DIR"

echo "Done: ${DIST_DIR}/${BUILD_FILE}"
