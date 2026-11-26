#!/usr/bin/bash
set -e

# Resolve the Box binary: explicit override, local install, then PATH.
if [ -n "${BOX_BIN:-}" ]; then
  BOX="$BOX_BIN"
elif [ -x vendor/bin/box ]; then
  BOX="vendor/bin/box"
elif command -v box >/dev/null 2>&1; then
  BOX="$(command -v box)"
else
  echo "Box not found. Install it with: composer global require humbug/box:^4.7 (or set BOX_BIN to a Box 4 binary)."
  exit 1
fi

# Build the phar
"$BOX" validate && "$BOX" compile
mkdir -p bin
mv -f eps.phar bin/eps.phar
echo "Phar built: bin/eps.phar"
