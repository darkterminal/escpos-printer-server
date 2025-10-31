<?php
declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\ReceiptPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReceiptPrinter::class)]
class ReceiptPrinterTest extends TestCase
{
    private ReceiptPrinter $printer;
    private array $mockPrinterSettings;
    private array $mockReceiptData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->printer = new ReceiptPrinter();

        $this->mockPrinterSettings = [
            "printer_name" => "Test-Printer-80mm",
            "interface" => "ethernet",
            "printer_host" => "127.0.0.1",
            "printer_port" => 12345,
            "template" => "epson",
            "pull_cash_drawer" => true,
            "line_feed_each_in_items" => 1,
            "more_new_line" => 0,
            "custom_print_header" => ["My Test Store"],
            "custom_print_footer" => ["Thank You"],
            "custom_language" => ["operator" => "Cashier"],
        ];

        $this->mockReceiptData = [
            "app_name" => "Test App",
            "full_name" => "Test User",
            "transaction" => [
                "date_transaction" => "2025-01-01",
                "transaction" => "TRX-123",
                "total_transaction" => 10000,
            ],
            "cart" => [
                [
                    "item_name" => "Test Item",
                    "price" => 10000,
                    "qty" => 1,
                    "sub_total" => 10000,
                ],
            ],
            "receipt_data" => [],
            "promo" => [],
        ];
    }

    #[Test]
    public function it_can_run_test_printer_and_produce_output(): void
    {
        $this->printer->printerSettings = $this->mockPrinterSettings;

        ob_start();
        $this->printer->testPrinter();
        $output = ob_get_clean();

        $this->assertStringContainsString("PRINTER TEST", $output);
        $this->assertStringContainsString("Test-Printer-80mm", $output); // Dari pengaturan kita
    }

    #[Test]
    public function it_throws_exception_when_printing_receipt_with_invalid_connection(): void
    {
        $this->expectException(\Exception::class);

        $this->printer->printerSettings = $this->mockPrinterSettings;
        $this->printer->receiptData = $this->mockReceiptData;

        $this->printer->printReceipt();
    }

    #[Test]
    public function it_throws_exception_when_pulling_cash_drawer_with_invalid_connection(): void
    {
        $this->expectException(\Exception::class);

        $this->printer->printerSettings = $this->mockPrinterSettings;

        $this->printer->pullCashDrawer();
    }
}
