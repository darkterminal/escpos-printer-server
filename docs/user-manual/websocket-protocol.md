---
title: WebSocket protocol
description: Single JSON object print job contract, printer settings, receipt data, and client examples.
section: Guide
eyebrow: API
---

## Protocol

The WebSocket server expects a **single JSON object** as the print job request.

## Top-level fields

| Key                | Type     | Required | Description |
| ------------------ | -------- | -------- | ----------- |
| `from`             | `string` | Yes      | Source id: `posclient`, `testprinter`, `checkprinter`, `pullcashdrawer`. |
| `printer_name`     | `string` | Yes      | The system-recognized printer name. |
| `printer_settings` | `object` | Yes      | Printer configuration and behavior options. |
| `receipt_data`     | `object` | Conditional | Required for `posclient` and `testprinter`; optional for others. |

## printer_settings

| Key                       | Type            | Required | Description |
| ------------------------- | --------------- | -------- | ----------- |
| `interface`               | `string`        | Yes      | One of: `cups`, `ethernet`, `linux-usb`, `smb`, `windows-usb`, `windows-lpt`. |
| `printer_host`            | `string`        | No       | Hostname or IP of the printer. |
| `printer_port`            | `integer`       | No       | TCP port of the printer. Default `9100`. |
| `template`                | `string`        | Yes      | `epson` or `custom`. |
| `pull_cash_drawer`        | `boolean`       | No       | When `true`, sends a pulse to open the cash drawer after printing. Default `false`. |
| `line_feed_each_in_items` | `integer`       | No       | Empty lines after each cart item. Default `1`. |
| `more_new_line`           | `integer`       | No       | Extra empty lines between sections. Default `0`. |
| `custom_print_header`     | `array[string]` | No       | Custom header lines. |
| `custom_print_footer`     | `array[string]` | No       | Custom footer lines. |
| `custom_language`         | `object`        | No       | Overrides receipt field labels. |

## receipt_data

| Key             | Type            | Required | Description |
| --------------- | --------------- | -------- | ----------- |
| `app_name`      | `string`        | Yes      | Store or POS system name in the header. |
| `store_address` | `string`        | No       | Physical store address. |
| `full_name`     | `string`        | Yes      | Cashier/operator name. |
| `transaction`   | `object`        | Yes      | Transaction-level information. |
| `cart`          | `array[object]` | Yes      | List of purchased items. |
| `promo`         | `array[string]` | No       | Promotional text near the footer. |

## Examples

### Print receipt (`posclient`)

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

### Check printer (`checkprinter`)

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

### Pull cash drawer (`pullcashdrawer`)

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

## Next steps

* 👉 [Validation](validation.md)
