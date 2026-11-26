#!/bin/bash
set -euo pipefail

REPO="darkterminal/escpos-printer-server"
INSTALL_DIR="${1:-${HOME}/.local/share/escpos-printer-server}"

detect_platform() {
    local os
    os="$(uname -s)"
    case "$os" in
        Linux) echo "linux" ;;
        Darwin) echo "darwin" ;;
        MINGW*|MSYS*|CYGWIN*) echo "windows" ;;
        *) echo "unknown" ;;
    esac
}

PLATFORM="$(detect_platform)"

if [ "$PLATFORM" = "windows" ]; then
    cat <<'EOF'
ESC/POS Printer Server installer: Windows detected.

Windows installer flow:
  1. Download the Windows bundle from the releases page:
     https://github.com/darkterminal/escpos-printer-server/releases/latest
  2. Extract it.
  3. Run eps-install.bat (installs and starts EPS-WS + EPS-HTTP via NSSM).
  4. For uninstall: run eps-uninstall.bat.

This script does not run Windows binaries from a non-Windows host.
EOF
    exit 0
fi

if [ "$PLATFORM" = "unknown" ]; then
    echo "Unsupported platform: $(uname -s). Aborting." >&2
    exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
    echo "ERROR: jq is required to resolve the latest release asset." >&2
    exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
    echo "ERROR: unzip is required to extract the bundle." >&2
    exit 1
fi

TAG="$(curl -fsSL "https://api.github.com/repos/${REPO}/releases/latest" | jq -r .tag_name)"
ASSET_URL="$(
    curl -fsSL "https://api.github.com/repos/${REPO}/releases/tags/${TAG}" |
    jq -r '.assets[] | select(.name | startswith("eps-unix-bundle-")) | .browser_download_url' |
    head -n 1
)"

if [ -z "$ASSET_URL" ]; then
    echo "ERROR: eps-unix-bundle asset not found for release ${TAG}." >&2
    exit 1
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
curl -fsSL -o "${TMP_DIR}/eps-unix-bundle.zip" "$ASSET_URL"
mkdir -p "$INSTALL_DIR"
unzip -q "${TMP_DIR}/eps-unix-bundle.zip" -d "$INSTALL_DIR"
chmod +x "${INSTALL_DIR}/eps"

echo "ESC/POS Printer Server installed to: ${INSTALL_DIR}"
echo "Next steps:"
echo "  cd ${INSTALL_DIR}"
echo "  ./eps --role http"
echo "  # then open http://localhost:1100 and use the Web GUI / check printer"
