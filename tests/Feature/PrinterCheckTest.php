<?php

declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\ReceiptPrinter;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;

test('checkPrinter reports the configured connection without a real printer', function () {
    $printer = new ReceiptPrinter;
    $printer->printerSettings = [
        'interface' => 'ethernet',
        'printer_host' => '192.168.1.150',
    ];

    ob_start();
    try {
        $result = $printer->checkPrinter(new DummyPrintConnector);
    } finally {
        ob_end_clean();
    }

    expect($result)->toBe([
        'ok' => true,
        'interface' => 'ethernet',
        'host' => '192.168.1.150',
    ]);
});

test('checkPrinter wraps connector failures in a RuntimeException', function () {
    $printer = new ReceiptPrinter;
    $printer->printerSettings = ['interface' => 'bogus'];

    ob_start();
    try {
        $printer->checkPrinter();
    } catch (\RuntimeException $e) {
        expect(str_starts_with($e->getMessage(), 'Printer check failed'))->toBeTrue()
            ->and($e->getPrevious())->toBeInstanceOf(\Exception::class);

        return;
    } finally {
        ob_end_clean();
    }

    $this->fail('checkPrinter did not throw a RuntimeException');
});
