#!/bin/bash

# Script to help create new releases

# Ensure script is run from the plugin root
if [ ! -f "corporate-documents.php" ]; then
    echo "Error: This script must be run from the plugin root directory"
    exit 1
fi

# Check if version number was provided
if [ -z "$1" ]; then
    echo "Usage: ./bin/release.sh <version>"
    echo "Example: ./bin/release.sh 1.1.0"
    exit 1
fi

VERSION=$1
CURRENT_BRANCH=$(git branch --show-current)

# Verify we're on main/master branch
if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "master" ]; then
    echo "Error: Must be on main/master branch to create a release"
    exit 1
fi

# Ensure working directory is clean
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: Working directory not clean. Commit or stash changes first."
    exit 1
fi

# Update version numbers
echo "Updating version numbers to $VERSION..."

# Update main plugin file version
sed -i '' "s/Version:.*$/Version:           $VERSION/" corporate-documents.php
sed -i '' "s/define('CDOX_VERSION'.*$/define('CDOX_VERSION', '$VERSION');/" corporate-documents.php

# Update readme.txt stable tag
if [ -f "readme.txt" ]; then
    sed -i '' "s/Stable tag:.*$/Stable tag: $VERSION/" readme.txt
fi

# Add changes to git
git add corporate-documents.php readme.txt

# Create changelog entry
echo "Creating changelog entry..."
CHANGELOG_FILE="CHANGELOG.md"

if [ ! -f "$CHANGELOG_FILE" ]; then
    echo "# Changelog" > "$CHANGELOG_FILE"
    echo "" >> "$CHANGELOG_FILE"
fi

DATE=$(date +%Y-%m-%d)
echo -e "## [$VERSION] - $DATE\n" | cat - "$CHANGELOG_FILE" > temp && mv temp "$CHANGELOG_FILE"

# Open editor for changelog
if [ -n "$EDITOR" ]; then
    $EDITOR "$CHANGELOG_FILE"
else
    vi "$CHANGELOG_FILE"
fi

# Add changelog to git
git add "$CHANGELOG_FILE"

# Commit version bump
git commit -m "Prepare release $VERSION"

# Create git tag
echo "Creating git tag v$VERSION..."
git tag -a "v$VERSION" -m "Release version $VERSION"

# Instructions for pushing
echo "Release preparation complete!"
echo "To finish the release, run:"
echo "git push origin main"
echo "git push origin v$VERSION"
echo ""
echo "The GitHub Action will automatically:"
echo "1. Create a GitHub release"
echo "2. Build the plugin"
echo "3. Attach the plugin zip to the release"