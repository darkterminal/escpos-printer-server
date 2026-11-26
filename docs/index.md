---
layout: default
title: "Receipt. Engineered."
description: "A simple and efficient ESC/POS printer server built with PHP, Workerman and ESC/POS-PHP, providing WebSocket-based printing for thermal receipt printers."
---

<div class="hero">
    <p class="eyebrow">ESC/POS // Industrial Printing</p>
    <h1>Receipt. Engineered.</h1>
    <p class="tagline">A simple and efficient ESC/POS printer server built with PHP (&gt;= 8.3), Workerman (^5.2) and ESC/POS-PHP (^5.0) — WebSocket-based printing for thermal receipt printers.</p>
    <div class="cta">
        <a class="btn primary" href="#quickstart">Quick Start</a>
        <a class="btn" href="user-manual/">User Manual</a>
    </div>
</div>

## Features

<div class="grid">
    <div class="card">
        <h3>Cross-Platform</h3>
        <p>Linux, macOS, and Windows. WebSocket server for real-time receipt printing.</p>
    </div>
    <div class="card">
        <h3>Multi-Interface Printer</h3>
        <p>CUPS, Ethernet, Linux-USB, SMB, Windows-USB, and Windows-LPT printer interfaces.</p>
    </div>
    <div class="card">
        <h3>WebSocket Server</h3>
        <p>Real-time receipt printing over a single JSON protocol.</p>
    </div>
    <div class="card">
        <h3>Web GUI</h3>
        <p>Built-in modern industrial-styled Web GUI for configuration and printer checks.</p>
    </div>
    <div class="card">
        <h3>Auth</h3>
        <p>Optional shared token via <code>EPS_AUTH_TOKEN</code>; loopback by default.</p>
    </div>
    <div class="card">
        <h3>Bundles</h3>
        <p>Reproducible Windows/Unix release bundles with checksum and layout verification.</p>
    </div>
</div>

## Quick Start

```bash
curl -fsSL https://raw.githubusercontent.com/darkterminal/escpos-printer-server/main/scripts/install.sh | bash

cd ~/.local/share/escpos-printer-server
./eps --role http
```

Open **http://localhost:1100**, click **Test Printer**, and check the CLI for print output.

## Documentation

- [User Manual](user-manual/) — installation, authentication, WebSocket payload contract, validation, and examples.
- [Releases](https://github.com/darkterminal/escpos-printer-server/releases)

---

**Latest release: [`2.0.8`](https://github.com/darkterminal/escpos-printer-server/releases/tag/2.0.8)** — documentation, installer, and banner updates.
