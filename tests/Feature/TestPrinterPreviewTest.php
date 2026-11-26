<?php

declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\ReceiptPrinter;

test('testPrinter prints a dummy receipt containing the store name', function () {
    $printer = new ReceiptPrinter;
    $printer->receiptData = [
        'app_name' => 'XYZ MART',
        'full_name' => 'Kasir A',
        'transaction' => [
            'date_transaction' => '2025-10-28',
            'transaction' => 'TRX20251028001',
            'total_transaction' => 90000,
        ],
        'cart' => [
            [
                'item_name' => 'Kopi Robusta 250gr',
                'price' => 45000,
                'qty' => 2,
                'sub_total' => 90000,
            ],
        ],
    ];
    $printer->printerSettings = [
        'template' => 'epson',
    ];

    ob_start();
    try {
        $printer->testPrinter();
        $output = ob_get_clean();
    } catch (\Throwable $e) {
        ob_end_clean();

        throw $e;
    }

    expect($output)->toContain('XYZ MART');
});
