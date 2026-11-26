#!/bin/bash
set -euo pipefail

RELEASE_DIR="bundle"

rm -rf "$RELEASE_DIR"
mkdir -p "$RELEASE_DIR"

NSSM_URL="https://nssm.cc/release/nssm-2.24.zip"
rm -rf /tmp/nssm.zip
curl -L -o /tmp/nssm.zip "$NSSM_URL"
unzip -q /tmp/nssm.zip -d /tmp/nssm_unpack
find /tmp/nssm_unpack -type f -iname "nssm.exe" -exec cp {} "$RELEASE_DIR/" \;
ls -la "$RELEASE_DIR" || true

LISTING="$(curl -s https://windows.php.net/downloads/releases/)"
# Keep the fallback reachable when the listing has no match (egrep returns 1
# and pipefail would otherwise abort the script).
PHP_ZIP=$(echo "$LISTING" \
| egrep -o 'php-8\.4\.[0-9]+-Win32-vs1[67]-x64\.zip' \
| sort -V | tail -n1) || true

if [ -z "$PHP_ZIP" ]; then
    echo "Fallback: Using php-8.4.13-Win32-vs17-x64.zip"
    PHP_ZIP="php-8.4.13-Win32-vs17-x64.zip"
fi

echo "Downloading $PHP_ZIP..."

curl -L -o /tmp/php8.4.zip "https://windows.php.net/downloads/releases/$PHP_ZIP"
unzip -q /tmp/php8.4.zip -d /tmp/php_unpack
mkdir -p "$RELEASE_DIR/php"
cp -r /tmp/php_unpack/* "$RELEASE_DIR/php/" || true
cp "$RELEASE_DIR/php/php.ini-production" "$RELEASE_DIR/php/php.ini"

# Enable required extensions
echo "extension=intl" >> "$RELEASE_DIR/php/php.ini"
echo "extension=sockets" >> "$RELEASE_DIR/php/php.ini"
echo "extension=openssl" >> "$RELEASE_DIR/php/php.ini"
echo "extension_dir=ext" >> "$RELEASE_DIR/php/php.ini"

ls -la "$RELEASE_DIR/php" | head -n 20

mkdir -p "$RELEASE_DIR/config/"
# Copy the whole `config` directory into the bundle so it appears as "$RELEASE_DIR/config"
cp -r config "$RELEASE_DIR/"
mkdir -p "$RELEASE_DIR/src/"
# Copy the whole `src` directory into the bundle so it appears as "$RELEASE_DIR/src"
cp -r src "$RELEASE_DIR/"
cp composer.json "$RELEASE_DIR/composer.json"
# Pin the in-bundle install to the same resolved versions as CI.
cp composer.lock "$RELEASE_DIR/composer.lock"
cp eps "$RELEASE_DIR/eps"

cp scripts/eps-install.bat "$RELEASE_DIR/eps-install.bat"
cp scripts/eps-uninstall.bat "$RELEASE_DIR/eps-uninstall.bat"

# === Install Composer inside bundle directory ===
echo "Installing Composer inside $RELEASE_DIR..."

cd "$RELEASE_DIR"

# Download installer using curl (more reliable than PHP copy)
curl -fsSL https://getcomposer.org/installer -o composer-setup.php

# Verify the installer hash against the live published value; never pin it.
EXPECTED_SIGNATURE="$(curl -fsSL https://composer.github.io/installer.sig || true)"

# The published hash is a SHA-384 digest: 96 hex characters.
if ! [[ "$EXPECTED_SIGNATURE" =~ ^[0-9a-fA-F]{96}$ ]]; then
    echo "ERROR: Could not fetch a valid Composer installer hash (96 hex chars required)!"
    rm -f composer-setup.php
    exit 1
fi

ACTUAL_SIGNATURE=$("php/php.exe" -r "echo hash_file('sha384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
    echo "ERROR: Invalid installer hash!"
    rm -f composer-setup.php
    exit 1
fi

echo "Installer verified."

"php/php.exe" composer-setup.php
rm -f composer-setup.php

# Use composer to install dependencies inside bundle
"php/php.exe" "composer.phar" install --no-dev --optimize-autoloader
rm composer.phar

# Gate the bundle before it can be shipped.
bash ../scripts/verify-bundle.sh .

cd ..
echo "Composer successfully installed and dependencies are ready."
