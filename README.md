<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="assets/eps-white.png">
      <source media="(prefers-color-scheme: light)" srcset="assets/eps-black.png">
      <img alt="Shows a black logo in light color mode and a white one in dark color mode." src="assets/eps-black.png">
    </picture>
</p>
<p align="center">A simple and efficient ESC/POS printer server built with PHP, <a href="https://github.com/walkor/workerman" target="_blank">Workerman</a> and <a href="https://github.com/mike42/escpos-php" target="_blank">ESC/POS-PHP</a>, providing WebSocket-based printing capabilities for thermal printers.</p>

## Features
- **Cross-platform**: Works on Linux, macOS, and Windows.
- **Multi-interface printer support**:
  - CUPS
  - Ethernet
  - Linux-USB
  - SMB
  - Windows-USB
  - Windows-LPT
- **WebSocket Server** for real-time receipt printing.
- **Built-in Web GUI configuration** accessible via HTTP.
- **Customizable receipt templates** (Epson or custom).
- **Cash drawer control** using ESC/POS pulse command.
- **Structured JSON configuration** for easy setup.
- **Logging system** with rotating daily logs.
- **Printer testing utility**.
- **Bundled Windows service installer (via NSSM).**

## Architecture Overview
The eps (ESC/POS Printer Server) executable bridges your web or desktop application and the thermal printer.
1. The client app (browser, POS app, or API) connects via WebSocket.
2. The EPS server receives JSON payloads and translates them to ESC/POS commands.
3. Commands are sent to the connected printer via the configured interface.

## Installation

### Windows
### Linux/MacOS

## Build from Source

### 1. Clone Repository
```bash
git clone https://github.com/darkterminal/escpos-printer-server.git
cd escpos-printer-server
```

### 2. Install Dependencies
```bash
composer install
```

## Running the Server

### Windows 
### Linux/MacOs
