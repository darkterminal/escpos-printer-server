---
title: Getting started
description: Install the ESC/POS Printer Server on Windows, Linux or macOS, and run the WebSocket and HTTP services.
section: Guide
eyebrow: First run
---

## Requirements

- PHP >= 8.3 with the `sockets`, `intl`, and `openssl` extensions.

## Install

### Linux / macOS

```bash
curl -fsSL https://raw.githubusercontent.com/darkterminal/escpos-printer-server/main/scripts/install.sh | bash
cd ~/.local/share/escpos-printer-server
./eps --role http
```

### Windows

1. Download the `eps-windows-bundle` archive from the [Release Page](https://github.com/darkterminal/escpos-printer-server/releases).
2. Extract it somewhere on your system.
3. Run `eps-install.bat` to install and start the `EPS-WS` and `EPS-HTTP` services via NSSM.

## Run

```bash
./eps --role http
```

The Web GUI is served at `http://localhost:1100`. The WebSocket server listens on `ws://localhost:1945`.

## Next steps

* 👉 [Authentication](authentication.md)
* 👉 [WebSocket protocol](websocket-protocol.md)
