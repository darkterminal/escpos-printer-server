<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="assets/eps-white.png">
      <source media="(prefers-color-scheme: light)" srcset="assets/eps-black.png">
      <img alt="Shows a black logo in light color mode and a white one in dark color mode." src="assets/eps-black.png">
    </picture>
</p>
<p align="center">A simple and efficient ESC/POS printer server built with PHP, <a href="https://github.com/walkor/workerman" target="_blank">Workerman</a> and <a href="https://github.com/mike42/escpos-php" target="_blank">ESC/POS-PHP</a>, providing WebSocket-based printing capabilities for thermal printers.</p>

## Features

- **Multi-Interface Supports**: CUPS, Ethernet, Linux-USB, SMB, Windows-USB, and Windows-LPT
- **WebSocket Server**: Real-time communication via WebSocket protocol
- **Template System**: Configurable Receipt templates (Epson and Custom)
- **Cash Drawer Control**: Open cash drawer with pulse command
- **Customizable Receipts**: Flexible header, footer, and content formatting
- **Easy Configuration**: Simple JSON-based configuration
- **Comprehensive Logging**: Built-in logging with daily log files
- **Testing Utilities**: Built-in printer testing functionality
- **Web GUI Configuration**: Built-in Web GUI configuration

## How It Works?

The ESC/POS Printer Server operates as a bridge between an Online Web Application and a Thermal Printer, utilizing a WebSocket Server powered by the `eps` (ESC/POS Printer Server) executable file, enabling the application to interact via a WebSocket Client (Browser) with the WebSocket Server (`eps`) and sending ESC/POS commands to the thermal printer that plug into local device.

## Printer Interfaces

- `cups` - The standards-based, open source printing system
- `ethernet` - Print via Ethernet interface
- `linux-usb` - Print via USB interface on Linux systems
- `smb` - Print via SMB (Server Message Block) protocol
- `windows-usb` - Print via USB interface on Windows systems
- `windows-lpt` - Print via LPT (Line Printer) protocol on Windows systems

## Usage

