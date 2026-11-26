---
title: Overview
description: What the ESC/POS Printer Server is, why it exists, and what it ships with.
section: Introduction
eyebrow: Product guide
---

## Introduction

## What is the ESC/POS Printer Server?

**ESC/POS Printer Server** is a simple and efficient ESC/POS printer server built with PHP, [Workerman](https://github.com/walkor/workerman) and [ESC/POS-PHP](https://github.com/mike42/escpos-php).

It bridges your web or POS application and the thermal printer:

1. The client app (browser, POS app, or API) connects via WebSocket.
2. The server receives JSON payloads and translates them to ESC/POS commands.
3. Commands are sent to the connected printer via the configured interface.

### Why use it?

Thermal receipt printers should not be a commodity you bolt onto a PHP app with ad-hoc socket code. This server owns the boring, hard parts:

- Real-time WebSocket delivery.
- Strict payload validation.
- Single timestamped response per job.
- Multi-interface printer matrix.

## Features

- **Cross-platform** — Linux, macOS, and Windows.
- **Multi-interface printer** — CUPS, Ethernet, Linux-USB, SMB, Windows-USB, Windows-LPT.
- **WebSocket server** — real-time receipt printing.
- **Web GUI** — industrial-styled configuration and printer check.
- **Auth** — optional shared token via `EPS_AUTH_TOKEN`.
- **Bundles** — reproducible Windows/Unix release bundles with checksum verification.

## Next steps

* 👉 [Getting started](installation.md)
* 👉 [Authentication](authentication.md)
* 👉 [WebSocket protocol](websocket-protocol.md)
* 👉 [Validation](validation.md)
