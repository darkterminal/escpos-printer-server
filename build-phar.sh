#!/usr/bin/env bash
set -e
# ensure box is installed (https://github.com/box-project/box2)
if ! command -v box >/dev/null 2>&1; then
  echo "Box not found. Please install box (composer global require humbug/box) or follow https://box-project.github.io/box2/"
  exit 1
fi

box validate && box compile
# move phar to predictable location
mkdir -p bin
mv -f eps.phar bin/eps.phar
echo "Phar built: bin/eps.phar"
