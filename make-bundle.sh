#!/bin/bash

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
PHP_ZIP=$(echo "$LISTING" \
| egrep -o 'php-8\.4\.[0-9]+-Win32-vs1[67]-x64\.zip' \
| sort -V | tail -n1)

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
cp -r config/ "$RELEASE_DIR/"
mkdir -p "$RELEASE_DIR/src/"
cp -r src/ "$RELEASE_DIR/"
cp composer.json "$RELEASE_DIR/composer.json"
cp eps "$RELEASE_DIR/eps"

cp scripts/eps-install.bat "$RELEASE_DIR/eps-install.bat"
cp scripts/eps-uninstall.bat "$RELEASE_DIR/eps-uninstall.bat"

# === Install Composer inside bundle directory ===
echo "Installing Composer inside $RELEASE_DIR..."

cd "$RELEASE_DIR"

sleep 5

# Download installer using curl (more reliable than PHP copy)
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Verify installer signature (optional but secure)
EXPECTED_SIGNATURE="ed0feb545ba87161262f2d45a633e34f591ebb3381f2e0063c345ebea4d228dd0043083717770234ec00c5a9f9593792"
ACTUAL_SIGNATURE=$("php/php.exe" -r "echo hash_file('sha384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
    echo "ERROR: Invalid installer signature!"
    rm composer-setup.php
    exit 1
fi

echo "Installer verified."

"php/php.exe" composer-setup.php
rm -f composer-setup.php

# Use composer to install dependencies inside bundle
"php/php.exe" "composer.phar" install --no-dev --optimize-autoloader
rm composer.phar

cd ..
echo "Composer successfully installed and dependencies are ready."
