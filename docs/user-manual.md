---
layout: default
title: "User Manual"
description: "User manual for ESC/POS Printer Server: installation, authentication, WebSocket payload contract, validation, and examples."
permalink: /user-manual/
---

# User Manual

Source of truth for installation, authentication, WebSocket protocol, validation, and examples.

## Installation

### Windows

1. Download the `eps-windows-bundle` archive from the [Release Page](https://github.com/darkterminal/escpos-printer-server/releases).
2. Extract it somewhere on your system.
3. Run `eps-install.bat` to install and start the `EPS-WS` and `EPS-HTTP` services via NSSM.
4. Need to uninstall? Run `eps-uninstall.bat`.

### Linux / macOS

Requirements: PHP >= 8.3 with the `sockets`, `intl`, and `openssl` extensions.

```bash
curl -fsSL https://raw.githubusercontent.com/darkterminal/escpos-printer-server/main/scripts/install.sh | bash
cd ~/.local/share/escpos-printer-server
./eps --role http
```

## Authentication

The WebSocket print job and the HTTP `GET` / `POST /api/config` endpoints are protected by an optional shared token.

- If `EPS_AUTH_TOKEN` is unset, only loopback clients (`127.0.0.1` and `::1`) are accepted.
- To serve remote clients, set `EPS_AUTH_TOKEN` in the service environment, restart the server, and send the same value as `auth_token` in every WebSocket payload or as the `X-EPS-Token` header on `/api/config` requests.
- The token is sent only in the WebSocket `auth_token` field or the `X-EPS-Token` header and is not persisted in `printer-settings.json`.

This is one shared token for every client, not per-user authentication; treat it as a secret.

## WebSocket Protocol

The WebSocket server expects a **single JSON object** as the print job request.

### Top-Level Fields

| Key                | Type     | Required | Description |
| ------------------ | -------- | -------- | ----------- |
| `from`             | `string` | Yes      | Source id: `posclient`, `testprinter`, `checkprinter`, `pullcashdrawer`. |
| `printer_name`     | `string` | Yes      | The system-recognized printer name. |
| `printer_settings` | `object` | Yes      | Printer configuration and behavior options. |
| `receipt_data`     | `object` | Conditional | Required for `posclient` and `testprinter`; optional for others. |

### Printer Settings

| Key                       | Type            | Required | Description |
| ------------------------- | --------------- | -------- | ----------- |
| `interface`               | `string`        | Yes      | One of: `cups`, `ethernet`, `linux-usb`, `smb`, `windows-usb`, `windows-lpt`. |
| `printer_host`            | `string`        | No       | Hostname or IP of the printer. Default empty. |
| `printer_port`            | `integer`       | No       | TCP port of the printer. Default `9100`. |
| `template`                | `string`        | Yes      | `epson` or `custom`. |
| `pull_cash_drawer`        | `boolean`       | No       | When `true`, sends a pulse to open the cash drawer after printing. Default `false`. |
| `line_feed_each_in_items` | `integer`       | No       | Empty lines after each cart item. Default `1`. |
| `more_new_line`           | `integer`       | No       | Extra empty lines between sections. Default `0`. |
| `custom_print_header`     | `array[string]` | No       | Custom header lines. |
| `custom_print_footer`     | `array[string]` | No       | Custom footer lines. |
| `custom_language`         | `object`        | No       | Overrides receipt field labels. |

### Receipt Data

| Key             | Type            | Required | Description |
| --------------- | --------------- | -------- | ----------- |
| `app_name`      | `string`        | Yes      | Store or POS system name in the header. |
| `store_address` | `string`        | No       | Physical store address. |
| `full_name`     | `string`        | Yes      | Cashier/operator name. |
| `transaction`   | `object`        | Yes      | Transaction-level information. |
| `cart`          | `array[object]` | Yes      | List of purchased items. |
| `promo`         | `array[string]` | No       | Promotional text near the footer. |

### Transaction Object

| Key                 | Type                   | Required | Description |
| ------------------- | --------------------- | -------- | ----------- |
| `date_transaction` | `string` (`YYYY-MM-DD`) | Yes   | Date of the transaction. |
| `transaction`      | `string`              | Yes      | Unique transaction number or invoice code. |
| `tax`               | `integer`             | No       | Applied tax amount. |
| `member_discount`   | `integer`             | No       | Member discount amount. |
| `total_transaction` | `integer`             | Yes      | Grand total before payment. |
| `paid`              | `integer`             | No       | Amount received; used to calculate change. |
| `paid_return`       | `integer`             | No       | Change returned to the customer. |
| `payment`           | `string`              | No       | Payment type, e.g. `Cash`, `Credit`, `Debit`. |
| `deposit`           | `integer`             | No       | Deposit amount. |
| `due_date`          | `string` (`YYYY-MM-DD`) | No   | Due date for credit transactions. |

### Cart Items

| Key         | Type      | Required | Description |
| ----------- | --------- | -------- | ----------- |
| `item_name` | `string`  | Yes      | Product name; auto-wrapped for long text. |
| `price`     | `integer` | Yes      | Unit price. |
| `qty`       | `integer` | Yes      | Quantity purchased. |
| `sub_total` | `integer` | No       | Defaults to `price * qty`. |

## Examples

### Print Receipt (`posclient`)

```json
{
    "from": "posclient",
    "printer_name": "IW-8001",
    "printer_settings": {
        "interface": "ethernet",
        "printer_host": "192.168.1.150",
        "printer_port": 9100,
        "template": "epson",
        "pull_cash_drawer": false
    },
    "receipt_data": {
        "app_name": "Coffee POS",
        "store_address": "Jl. Industrial No. 1",
        "full_name": "Operator Name",
        "transaction": {
            "date_transaction": "2026-11-26",
            "transaction": "INV-00123",
            "tax": 0,
            "total_transaction": 15000,
            "paid": 20000,
            "payment": "Cash"
        },
        "cart": [
            {
                "item_name": "Espresso",
                "price": 5000,
                "qty": 2,
                "sub_total": 10000
            }
        ]
    }
}
```

### Check Printer (`checkprinter`)

```json
{
    "from": "checkprinter",
    "printer_name": "IW-8001",
    "printer_settings": {
        "interface": "ethernet",
        "printer_host": "192.168.1.150",
        "printer_port": 9100,
        "template": "epson"
    }
}
```

### Pull Cash Drawer (`pullcashdrawer`)

```json
{
    "from": "pullcashdrawer",
    "printer_name": "IW-8001",
    "printer_settings": {
        "interface": "ethernet",
        "printer_host": "192.168.1.150",
        "printer_port": 9100,
        "template": "epson",
        "pull_cash_drawer": true
    }
}
```

## Validation and Errors

Every message is validated. Invalid payloads produce a single timestamped error response, e.g.:

```text
[2026/11/26 15:00:00][ERROR] Invalid payload: from must be a non-empty string
```

A valid job produces exactly one timestamped success response, e.g.:

```text
[2026/11/26 15:00:00][SUCCESS] Receipt printed successfully
```

Use `checkprinter` to open the configured printer connection and return a result without printing paper; the reply is a status message such as `[SUCCESS] Printer connection OK (ethernet)`.
