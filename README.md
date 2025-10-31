<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="assets/eps-white.png">
      <source media="(prefers-color-scheme: light)" srcset="assets/eps-black.png">
      <img alt="Shows a black logo in light color mode and a white one in dark color mode." src="assets/eps-black.png">
    </picture>
</p>
<p align="center">A simple and efficient ESC/POS printer server built with PHP, <a href="https://github.com/walkor/workerman" target="_blank">Workerman</a> and <a href="https://github.com/mike42/escpos-php" target="_blank">ESC/POS-PHP</a>, providing WebSocket-based printing capabilities for thermal printers.</p>

<p align="center">
  <a href="https://saweria.co/darkterminal" target="_blank"><img alt="Static Badge" src="https://img.shields.io/badge/Donate-Saweria-blue"></a>
  <a href="https://github.com/sponsors/darkterminal" target="_blank"><img alt="GitHub Sponsors" src="https://img.shields.io/github/sponsors/darkterminal"></a>
  <img alt="GitHub Actions Workflow Status" src="https://img.shields.io/github/actions/workflow/status/darkterminal/escpos-printer-server/create-bundle.yml">
  <img alt="GitHub License" src="https://img.shields.io/github/license/darkterminal/escpos-printer-server">
  <img alt="GitHub Release" src="https://img.shields.io/github/v/release/darkterminal/escpos-printer-server">
</p>

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

1. Download `eps-windows-bundle` archive from the [Release Page](https://github.com/darkterminal/escpos-printer-server/releases)
2. Extract somewhere on your system
3. Open directory or extracted content
4. Double Click `eps-install.bat` to install and run ESC/POS Printer Server as a Windows Services
5. Need to uninstall? double click `eps-uninstall.bat` to stop and remove ESC/POS Printer Server from Windows Service

### Linux/MacOS

**Requirement**

- PHP >= 8.3 (Installed)
- PHP Extensions:
  - sockets
  - intl
  - openssl

**Installation Process**

1. Download `eps-unix-bundle` archive from the [Release Page](https://github.com/darkterminal/escpos-printer-server/releases)
2. Extract somewhere on your system
3. Open directory or extracted content
4. See usage section

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

### 3. Build Phar
```bash
composer run create:phar
```
> Note: Phar is generated inside `bin` directory

## Usage

### Windows

ESC/POS Printer Server is already installed on Windows Service Manager.

### Linux/MacOS

1. Open your terminal inside extracted content
2. the run this command `php eps.phar start` or `php eps.phar start -d` to run as a daemon.

## Web GUI & Integration

- Web GUI on: `http://localhost:1100`
- ESC/POS WebSocket Server: `http://localhost:1945`

### Printer Settings Payload

![Printer Settings Payload](https://i.imgur.com/8Rfx2jT.png)

## WebSocket Communication

The WebSocket server expects a **single JSON object** as the print job request.
This object contains the print source, printer configuration, and receipt data.

### Example

```json
{
  "from": "posclient",
  "printer_name": "IW-8001",
  "printer_settings": { ... },
  "receipt_data": { ... }
}
```

---

**Top-Level Fields**

| Key                | Type     | Required | Description                                                                                                                                                                                     |
| ------------------ | -------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `from`             | `string` | **Yes**  | Identifier for the client or request source. Used to distinguish between standard printing, test prints, or cash drawer triggers. Examples: `"posclient"`, `"testprinter"`, `"pullcashdrawer"`. |
| `printer_name`     | `string` | **Yes**  | The system-recognized printer name. On Linux (CUPS) it matches the printer queue name; on Windows it matches the printer’s registered device name.                                              |
| `printer_settings` | `object` | **Yes**  | Printer configuration and behavior options. Defines how the server connects to the printer, layout preferences, and text settings.                                                              |
| `receipt_data`     | `object` | **Yes**  | Contains all data that will be printed on the receipt, including header, items, totals, and optional promotional or footer sections.                                                            |

---

**Printer Settings (`printer_settings`)**

| Key                       | Type            | Required | Description                                                                                                                                  |
| ------------------------- | --------------- | -------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `interface`               | `string`        | **Yes**  | Communication interface for the printer. Supported values: `"ethernet"`, `"cups"`, `"linux-usb"`, `"windows-usb"`, `"windows-lpt"`, `"smb"`. |
| `printer_host`            | `string`        | Optional | Hostname or IP address of the printer (used only for network/ethernet printers).                                                             |
| `printer_port`            | `integer`       | Optional | TCP port number for the printer (default: `9100`).                                                                                           |
| `template`                | `string`        | **Yes**  | Printer layout template type. Supported: `"epson"` or `"custom"`. Determines font sizes and column widths.                                   |
| `pull_cash_drawer`        | `boolean`       | Optional | When `true`, sends a pulse signal to open the cash drawer after printing.                                                                    |
| `line_feed_each_in_items` | `integer`       | Optional | Number of line feeds (empty lines) after each item in the cart. Default: `1`.                                                                |
| `more_new_line`           | `integer`       | Optional | Adds additional empty lines between sections to increase spacing (useful for custom templates).                                              |
| `custom_print_header`     | `array[string]` | Optional | Custom header lines (store info, slogan, etc.). Each array element represents one printed line.                                              |
| `custom_print_footer`     | `array[string]` | Optional | Custom footer lines (thank-you message, return policy, etc.).                                                                                |
| `custom_language`         | `object`        | Optional | Overrides the default labels for receipt fields (e.g., `“Operator”`, `“Time”`, `“Total”`). See the next section for supported keys.          |

**Supported Custom Language Keys**

| Key                  | Description                                      |
| -------------------- | ------------------------------------------------ |
| `operator`           | Label for the cashier/operator name              |
| `time`               | Label for transaction time                       |
| `transaction_number` | Label for transaction ID                         |
| `customer_name`      | Label for customer name                          |
| `tax`                | Label for tax amount                             |
| `member`             | Label for member discount                        |
| `total`              | Label for total transaction amount               |
| `paid`               | Label for amount paid                            |
| `return`             | Label for change amount                          |
| `due_date`           | Label for due date (used in credit transactions) |
| `saving`             | Label for customer deposit                       |
| `loan`               | Label for loan amount (in credit purchases)      |

---

**Receipt Data (`receipt_data`)**

This object defines the actual content of the printed receipt.
Each field maps to a specific section printed by `ReceiptPrinter`.

| Key             | Type            | Required | Description                                                                                     |
| --------------- | --------------- | -------- | ----------------------------------------------------------------------------------------------- |
| `app_name`      | `string`        | **Yes**  | Store or POS system name displayed in the header.                                               |
| `store_address` | `string`        | Optional | Physical store address printed under the store name. If missing, defaults to “Default Address”. |
| `full_name`     | `string`        | **Yes**  | Name of the cashier/operator processing the transaction.                                        |
| `transaction`   | `object`        | **Yes**  | Transaction-level information such as transaction number, total, and payment details.           |
| `cart`          | `array[object]` | **Yes**  | List of purchased items, each containing name, price, quantity, and subtotal.                   |
| `promo`         | `array[string]` | Optional | Promotional text or campaign messages printed near the footer.                                  |

---

**Transaction Object (`receipt_data.transaction`)**

| Key                 | Type                  | Required | Description                                                                                                   |
| ------------------- | --------------------- | -------- | ------------------------------------------------------------------------------------------------------------- |
| `date_transaction`  | `string (YYYY-MM-DD)` | **Yes**  | Date of the transaction. Used for time-stamping the receipt.                                                  |
| `transaction`       | `string`              | **Yes**  | Unique transaction number or invoice code.                                                                    |
| `customer_name`     | `string`              | Optional | Customer name (defaults to “Walk-in Customer”).                                                               |
| `tax`               | `integer`             | Optional | Applied tax amount (0 if not applicable).                                                                     |
| `member_discount`   | `integer`             | Optional | Discount amount for members or loyalty programs.                                                              |
| `total_transaction` | `integer`             | **Yes**  | Grand total for the purchase, before payment.                                                                 |
| `paid`              | `integer`             | Optional | Amount received from customer (used to calculate change).                                                     |
| `paid_return`       | `integer`             | Optional | Change amount returned to the customer.                                                                       |
| `payment`           | `string`              | Optional | Payment type (e.g., `"Cash"`, `"Credit"`, `"Debit"`). Used to decide whether to print credit-related details. |
| `deposit`           | `integer`             | Optional | Deposit amount (used in partial or credit transactions).                                                      |
| `due_date`          | `string (YYYY-MM-DD)` | Optional | Due date for credit-based transactions.                                                                       |

---

**Cart Items (`receipt_data.cart[]`)**

| Key         | Type      | Required | Description                                                            |
| ----------- | --------- | -------- | ---------------------------------------------------------------------- |
| `item_name` | `string`  | **Yes**  | Product name or item description. Automatically wrapped for long text. |
| `price`     | `integer` | **Yes**  | Unit price of the item.                                                |
| `qty`       | `integer` | **Yes**  | Quantity purchased.                                                    |
| `sub_total` | `integer` | Optional | Total price for this item (defaults to `price * qty` if missing).      |

---

**Promo Messages (`receipt_data.promo[]`)**

| Type            | Required | Description                                                                                                                                                       |
| --------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `array[string]` | Optional | Each element represents a promotional line or message printed between the item list and the footer. Example: `"Buy 2 get 1 free"` or `"Member discount applied"`. |

---

**Example: Full Payload**

```json
{
  "from": "testprinter",
  "printer_name": "IW-8001",
  "printer_settings": {
    "printer_name": "IW-8001",
    "interface": "ethernet",
    "printer_host": "localhost",
    "printer_port": 1100,
    "template": "epson",
    "pull_cash_drawer": true,
    "line_feed_each_in_items": 1,
    "more_new_line": 0,
    "custom_print_header": [
      "DARKTERMINAL MART",
      "Jl. Merdeka No. 45, Tegal",
      "Open 07:30 - 16:30"
    ],
    "custom_print_footer": [
      "Darkterminal Mart",
      "Belanja Nyaman, Harga Aman"
    ],
    "custom_language": {
      "operator": "Kasir",
      "time": "Waktu",
      "transaction_number": "No TRX",
      "customer_name": "Nama Pelanggan",
      "tax": "Pajak",
      "member": "Diskon Member",
      "total": "Total Belanja",
      "paid": "Tunai",
      "return": "Kembalian",
      "due_date": "Jatuh Tempo",
      "saving": "Tabungan",
      "loan": "Piutang"
    }
  },
  "receipt_data": {
    "app_name": "XYZ MART",
    "store_address": "Jl. Universe Metaserve",
    "full_name": "Wagiman Artomoro",
    "transaction": {
      "date_transaction": "2025-10-28",
      "transaction": "TRX20251028001",
      "customer_name": "Dewi Lestari",
      "tax": 3000,
      "member_discount": 2000,
      "total_transaction": 90000,
      "paid": 100000,
      "paid_return": 10000,
      "payment": "Cash",
      "deposit": 0,
      "due_date": null
    },
    "cart": [
      {
        "item_name": "Kopi Robusta 250gr",
        "price": 45000,
        "qty": 2,
        "sub_total": 90000
      },
      {
        "item_name": "Gula Aren Premium 500gr",
        "price": 38000,
        "qty": 1,
        "sub_total": 38000
      }
    ],
    "promo": [
      "Beli 2 gratis 1 untuk produk tertentu",
      "Diskon member 10% untuk semua kopi lokal"
    ]
  }
}
```

**Example: Minimal Payload**

```json
{
  "from": "posclient",
  "printer_name": "IW-8001",
  "printer_settings": {
    "interface": "ethernet",
    "printer_host": "192.168.1.150",
    "printer_port": 9100,
    "template": "epson"
  },
  "receipt_data": {
    "app_name": "XYZ MART",
    "full_name": "Kasir A",
    "transaction": {
      "date_transaction": "2025-10-28",
      "transaction": "TRX20251028001",
      "total_transaction": 90000
    },
    "cart": [
      {
        "item_name": "Kopi Robusta 250gr",
        "price": 45000,
        "qty": 2,
        "sub_total": 90000
      }
    ]
  }
}
```

### WebSocket Client Example (JavaScript)

```javascript
const ws = new WebSocket("ws://localhost:1945");
ws.onopen = () => ws.send(JSON.stringify(escposPayload));
```

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md) to contribte this project.
