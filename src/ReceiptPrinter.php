<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer;

use Darkterminal\EscposPrinterServer\Utils\CommonTrait;
use Darkterminal\EscposPrinterServer\Utils\LoggerTrait;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class ReceiptPrinter
{
    use CommonTrait;
    use LoggerTrait;

    public array $receiptData;
    public array $printerSettings;

    public function __construct() {}

    /**
     * Print receipt based on the provided data
     */
    public function printReceipt(): void
    {
        try {
            // Validate receipt data
            $this->validateReceiptData($this->receiptData);

            // Create printer connector
            $connector = $this->createPrinterConnector($this->printerSettings);
            $printer = new Printer($connector);

            // Print all sections
            $this->printHeader(
                $printer,
                $this->receiptData,
                $this->printerSettings
            );
            $this->printSubHeader(
                $printer,
                $this->receiptData,
                $this->printerSettings
            );
            $this->printOperatorDetails(
                $printer,
                $this->receiptData,
                $this->printerSettings
            );
            $this->printShoppingDetails(
                $printer, 
                $this->receiptData, 
                $this->printerSettings
            );
            $this->printPromoSection(
                $printer, 
                $this->receiptData, 
                $this->printerSettings
            );
            $this->printFooter(
                $printer, 
                $this->receiptData, 
                $this->printerSettings
            );
            $this->printSubFooter(
                $printer, 
                $this->printerSettings
            );

            // Cut and pulse if configured
            $printer->cut();
            if ($this->printerSettings['pull_cash_drawer'] ?? false) {
                $printer->pulse();
            }

            $printer->close();

            $this->info(message: 'Receipt printed successfully');
        } catch (\Exception $e) {
            $this->error('Failed to print receipt: ' . $e->getMessage());
            throw $e;
        }
    }

    public function pullCashDrawer(): void
    {
        try {
            $connector = $this->createPrinterConnector($this->printerSettings);
            $printer = new Printer($connector);
            $printer->pulse();
            $printer->close();
            $this->info('Cash drawer pulled successfully');
        } catch (\Exception $e) {
            $this->error('Failed to pull cash drawer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test printer functionality
     */
    public function testPrinter(): void
    {
        try {
            $connector = new DummyPrintConnector();
            CapabilityProfile::load("TSP600");
            $printer = new Printer($connector);

            $printer->initialize();
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("PRINTER TEST\n");
            $printer->text("Date: " . date('Y-m-d H:i:s') . "\n");
            $printer->text("Printer: " . ($this->printerSettings['printer_name'] ?? 'Unknown') . "\n");
            $printer->feed(2);
            $printer->cut();

            $data = $connector->getData();

            echo $data . PHP_EOL;

            $printer->close();

            $this->info('Printer test completed');
        } catch (\Exception $e) {
            $this->error('Printer test failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create printer connector based on settings
     */
    private function createPrinterConnector(array $settings): CupsPrintConnector|FilePrintConnector|NetworkPrintConnector|WindowsPrintConnector
    {
        $allowed_interfaces = ['cups', 'ethernet', 'linux-usb', 'smb', 'windows-usb', 'windows-lpt'];

        if (!in_array($settings['interface'] ?? '', $allowed_interfaces)) {
            throw new \Exception('Invalid printer interface: ' . ($settings['interface'] ?? 'none'));
        }

        switch ($settings['interface']) {
            case 'cups':
                return new CupsPrintConnector($settings['printer_name']);
            case 'ethernet':
                $host = $settings['printer_host'] ?? $settings['printer_name'];
                $port = $settings['printer_port'] ?? 9100;
                return new NetworkPrintConnector($host, $port);
            case 'linux-usb':
                return new FilePrintConnector($settings['printer_name']);
            case 'smb':
            case 'windows-usb':
            case 'windows-lpt':
            default:
                return new WindowsPrintConnector($settings['printer_name']);
        }
    }

    /**
     * Validate receipt data structure
     */
    private function validateReceiptData(array $receiptData): void
    {
        $required_fields = ['app_name', 'full_name', 'transaction', 'cart'];

        foreach ($required_fields as $field) {
            if (!isset($receiptData[$field])) {
                throw new \Exception("Missing required receipt field: $field");
            }
        }

        if (!is_array($receiptData['receipt_data'])) {
            throw new \Exception('Receipt data must be an array');
        }

        if (isset($receiptData['transaction'])) {
            $required_transaction_fields = ['date_transaction', 'transaction', 'total_transaction'];
            foreach ($required_transaction_fields as $field) {
                if (!isset($receiptData['transaction'][$field])) {
                    throw new \Exception("Missing required transaction field: $field");
                }
            }
        }
    }

    /**
     * Get font settings based on template
     */
    private function getFontSettings(string $template): array
    {
        $selectPrinterFont = [
            'A' => Printer::FONT_A,
            'B' => Printer::FONT_B,
            'C' => Printer::FONT_C
        ];

        if ($template === 'epson') {
            return [
                'header_receipt' => $selectPrinterFont['B'],
                'sub_header_receipt' => $selectPrinterFont['B'],
                'detail_operator' => $selectPrinterFont['C'],
                'detail_cart' => $selectPrinterFont['C'],
                'sub_promo' => $selectPrinterFont['B'],
                'footer' => $selectPrinterFont['C'],
                'sub_footer' => $selectPrinterFont['B']
            ];
        } else {
            return [
                'header_receipt' => $selectPrinterFont['C'],
                'sub_header_receipt' => $selectPrinterFont['C'],
                'detail_operator' => $selectPrinterFont['C'],
                'detail_cart' => $selectPrinterFont['C'],
                'sub_promo' => $selectPrinterFont['C'],
                'footer' => $selectPrinterFont['C'],
                'sub_footer' => $selectPrinterFont['C']
            ];
        }
    }

    /**
     * Print receipt header
     */
    private function printHeader(Printer $printer, array $receiptData, array $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);
        $column_width = ($printerSettings['template'] === 'epson') ? 40 : 48;

        $printer->initialize();
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setFont($fontSettings['header_receipt']);
        $printer->setEmphasis(true);
        $printer->text($receiptData['app_name'] . "\n");
        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    /**
     * Print receipt sub header
     */
    private function printSubHeader(Printer $printer, array $receiptData, array $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);

        $printer->initialize();
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setFont($fontSettings['sub_header_receipt']);
        $printer->setEmphasis(true);

        if (!empty($printerSettings['custom_print_header'])) {
            foreach ($printerSettings['custom_print_header'] as $header_line) {
                $header_text = str_replace(['\r\n', '<br>', '<br/>'], "\n", $header_line);
                $printer->text(strtoupper($header_text) . "\n");
            }
        } else {
            $printer->text("SERVING WHOLEHEARTEDLY\n");
            $printer->text(strtoupper(str_replace(["<br>", "<br/>"], "", $receiptData['store_address'] ?? "Default Address")) . "\n");
            $printer->text("Open 07:30 - 16:30\n");
        }

        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    /**
     * Print operator details
     */
    private function printOperatorDetails(Printer $printer, array $receiptData, array $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);
        $column_width = ($printerSettings['template'] === 'epson') ? 40 : 48;
        $optnl = ($printerSettings['template'] === 'epson') ? 0 : ($printerSettings['more_new_line'] ?? 0);
        $more_new_line = str_repeat("\n", $optnl);
        $divider = str_repeat("-", $column_width) . "\n";

        $printer->initialize();
        $printer->setLineSpacing(20);
        $printer->setFont($fontSettings['detail_operator']);
        $printer->setEmphasis(true);
        $printer->text($divider);

        $custom_lang = $printerSettings['custom_language'] ?? [];

        $printer->text($this->makeAlignText(
            ($custom_lang['operator'] ?? 'Operator') . " : ",
            $receiptData['full_name'] . $more_new_line,
            $column_width
        ));

        $date_transaction = $receiptData['transaction']['date_transaction'] !== date('Y-m-d')
            ? $receiptData['transaction']['date_transaction']
            : date('Y-m-d');

        $printer->text($this->makeAlignText(
            ($custom_lang['time'] ?? 'Time') . " : ",
            $date_transaction . ' ' . date('H:i:s') . $more_new_line,
            $column_width
        ));

        $printer->text($this->makeAlignText(
            ($custom_lang['transaction_number'] ?? 'Transaction Number') . " : ",
            $receiptData['transaction']['transaction'] . $more_new_line,
            $column_width
        ));

        $printer->text($this->makeAlignText(
            ($custom_lang['customer_name'] ?? 'Customer Name') . " : ",
            ($receiptData['transaction']['customer_name'] ?? 'Walk-in Customer') . $more_new_line,
            $column_width
        ));

        $printer->text($divider);
        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    /**
     * Print shopping details
     */
    private function printShoppingDetails(Printer $printer, array $receiptData, array $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);

        $printer->initialize();
        $printer->setLineSpacing(20);
        $printer->setFont($fontSettings['detail_cart']);
        $printer->setEmphasis(true);

        foreach ($receiptData['cart'] as $item) {
            $sub_total_price = $item['sub_total'] ?? (int) $item['price'] * (int) $item['qty'];
            $printer->text($this->makeWrapText(
                $item['item_name'],
                $this->toIDR($sub_total_price, false),
                $printerSettings['template']
            ));
            $printer->text('@ ' . $this->toIDR($item['price'], true) . " x " . $item['qty'] . "\n");
            $printer->feed($printerSettings['line_feed_each_in_items'] ?? 1);
        }

        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    /**
     * Print promotional section
     */
    private function printPromoSection(Printer $printer, array $receiptData, array $printerSettings): void
    {
        if (empty($receiptData['promo'])) {
            return;
        }

        $fontSettings = $this->getFontSettings($printerSettings['template']);
        $column_width = ($printerSettings['template'] === 'epson') ? 40 : 48;
        $divider = str_repeat("-", $column_width) . "\n";

        $printer->initialize();
        $printer->setLineSpacing(20);
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setFont($fontSettings['sub_promo']);
        $printer->setEmphasis(true);
        $printer->text($divider);

        foreach ($receiptData['promo'] as $promo) {
            $printer->text(wordwrap($promo, 35, "\n") . "\n\n");
        }

        $printer->setEmphasis(false);
        $printer->feed(3);
    }

    /**
     * Print receipt footer
     */
    private function printFooter(Printer $printer, array $receiptData, array $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);
        $column_width = ($printerSettings['template'] === 'epson') ? 40 : 48;
        $optnl = ($printerSettings['template'] === 'epson') ? 0 : ($printerSettings['more_new_line'] ?? 0);
        $more_new_line = str_repeat("\n", $optnl);
        $divider = str_repeat("-", $column_width) . "\n";

        $printer->initialize();
        $printer->setLineSpacing(20);
        $printer->setFont($fontSettings['footer']);
        $printer->setEmphasis(true);
        $printer->text($divider);

        $custom_lang = $printerSettings['custom_language'] ?? [];

        // Tax
        if (($receiptData['transaction']['tax'] ?? 0) != 0) {
            $printer->text($this->makeAlignText(
                $custom_lang['tax'] ?? 'Tax',
                $this->toIDR($receiptData['transaction']['tax']),
                $column_width
            ) . $more_new_line);
        }

        // Member discount
        if (($receiptData['transaction']['member_discount'] ?? 0) != 0) {
            $printer->text($this->makeAlignText(
                $custom_lang['member'] ?? 'Member Discount',
                $this->toIDR($receiptData['transaction']['member_discount']),
                $column_width
            ) . $more_new_line);
        }

        // Total
        $printer->text($this->makeAlignText(
            $custom_lang['total'] ?? 'Total',
            $this->toIDR($receiptData['transaction']['total_transaction']),
            $column_width
        ) . $more_new_line);

        // Paid amount
        $bayar = ($receiptData['transaction']['paid'] ?? 0) != 0
            ? $this->toIDR($receiptData['transaction']['paid'])
            : '-';
        $printer->text($this->makeAlignText(
            $custom_lang['paid'] ?? 'Paid',
            $bayar,
            $column_width
        ) . $more_new_line);

        // Return amount
        $text_kembalian = ($receiptData['transaction']['payment'] ?? '') != 'Kredit'
            ? $this->toIDR($receiptData['transaction']['paid_return'] ?? 0)
            : '-';
        $printer->text($this->makeAlignText(
            $custom_lang['return'] ?? 'Return',
            $text_kembalian,
            $column_width
        ) . $more_new_line);

        // Credit transaction details
        if (($receiptData['transaction']['payment'] ?? '') == 'Kredit') {
            $printer->text($this->makeAlignText(
                $custom_lang['due_date'] ?? 'Due Date',
                date('d/m/Y', strtotime($receiptData['transaction']['due_date'] ?? date('Y-m-d'))),
                $column_width
            ) . $more_new_line);

            $printer->text($this->makeAlignText(
                $custom_lang['saving'] ?? 'Deposit',
                $this->toIDR($receiptData['transaction']['deposit'] ?? 0),
                $column_width
            ) . $more_new_line);

            $loan_amount = ($receiptData['transaction']['total_transaction'] ?? 0) - ($receiptData['transaction']['deposit'] ?? 0);
            $printer->text($this->makeAlignText(
                $custom_lang['loan'] ?? 'Loan',
                $this->toIDR($loan_amount),
                $column_width
            ) . $more_new_line);
        }

        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    /**
     * Print receipt sub footer
     */
    private function printSubFooter(Printer $printer, $printerSettings): void
    {
        $fontSettings = $this->getFontSettings($printerSettings['template']);

        $printer->initialize();
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setFont($fontSettings['sub_footer']);
        $printer->setEmphasis(true);

        if (!empty($printerSettings['custom_print_footer'])) {
            $printer->text("Thank you for shopping at\n");
            foreach ($printerSettings['custom_print_footer'] as $footer_line) {
                $printer->text("{$footer_line}\n");
            }
        } else {
            $printer->text("Thank you for shopping at\n");
            $printer->text("Your Store Name\n");
            $printer->text("Items that have been purchased cannot be\n");
            $printer->text("returned. Please take your receipt.\n");
        }

        $printer->setEmphasis(false);
        $printer->feed(3);
    }
}
