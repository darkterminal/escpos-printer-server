#!/usr/bin/bash
set -e

# Check if box is installed
if ! command -v vendor/bin/box >/dev/null 2>&1; then
  echo "Box not found. Please install box (composer global require humbug/box) or follow https://box-project.github.io/box2/"
  exit 1
fi

# Build the phar
vendor/bin/box validate && vendor/bin/box compile
mkdir -p bin
mv -f eps.phar bin/eps.phar
echo "Phar built: bin/eps.phar"
