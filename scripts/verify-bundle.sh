#!/bin/bash
set -euo pipefail

# Validate the layout of an extracted Windows bundle. Runs on a Linux host too:
# the Windows binaries are checked for presence only, never executed here.
if [ "$#" -ne 1 ]; then
    echo "usage: $0 <bundle-dir>" >&2
    exit 1
fi

BUNDLE_DIR="$1"
VERIFY_PHP="${VERIFY_PHP:-php}"

required_paths=(
    "src/functions.php"
    "config/settings.html"
    "config/printer-settings.json"
    "composer.json"
    "composer.lock"
    "vendor/autoload.php"
    "eps"
    "eps-install.bat"
    "eps-uninstall.bat"
    "nssm.exe"
    "php/php.exe"
)

for path in "${required_paths[@]}"; do
    if [ ! -e "$BUNDLE_DIR/$path" ]; then
        echo "bundle layout error: missing $path" >&2
        exit 1
    fi
done

for path in "config/config" "src/src"; do
    if [ -e "$BUNDLE_DIR/$path" ]; then
        echo "bundle layout error: unexpected $path" >&2
        exit 1
    fi
done

if ! (cd "$BUNDLE_DIR" && "$VERIFY_PHP" -r 'require "vendor/autoload.php"; exit(function_exists("argv2assoc") ? 0 : 1);'); then
    echo "bundle layout error: autoloader did not provide argv2assoc" >&2
    exit 1
fi

echo "bundle layout ok"
