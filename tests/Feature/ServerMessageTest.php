<?php

declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\Server;
use Workerman\Connection\TcpConnection;

function serverPayload(array $overrides = []): array
{
    $payload = [
        'from' => 'posclient',
        'printer_name' => 'IW-8001',
        'printer_settings' => [
            'interface' => 'linux-usb',
            'printer_name' => tempnam(sys_get_temp_dir(), 'escpos-server-test-'),
            'template' => 'epson',
        ],
        'receipt_data' => [
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
        ],
    ];

    return array_replace($payload, $overrides);
}

function dispatchMessage(TcpConnection $connection, string $payload): ?\Throwable
{
    ob_start();
    try {
        (new Server)->onMessage($connection, $payload);
    } catch (\Throwable $e) {
        return $e;
    } finally {
        ob_end_clean();
    }

    return null;
}

test('valid posclient payload sends exactly one success response', function () {
    $payload = serverPayload();
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, '[SUCCESS]')));

    expect(dispatchMessage($connection, json_encode($payload, JSON_THROW_ON_ERROR)))->toBeNull();

    unlink($payload['printer_settings']['printer_name']);
});

test('checkprinter payload without receipt_data sends one Printer connection OK response', function () {
    $payload = serverPayload(['from' => 'checkprinter']);
    unset($payload['receipt_data']);
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'Printer connection OK')));

    expect(dispatchMessage($connection, json_encode($payload, JSON_THROW_ON_ERROR)))->toBeNull();

    unlink($payload['printer_settings']['printer_name']);
});

test('payload missing receipt_data sends exactly one invalid payload response', function () {
    $payload = serverPayload();
    unset($payload['receipt_data']);
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'Invalid payload')));

    expect(dispatchMessage($connection, json_encode($payload, JSON_THROW_ON_ERROR)))->toBeNull();

    unlink($payload['printer_settings']['printer_name']);
});

test('null receipt_data sends one error response and throws nothing', function () {
    $payload = serverPayload(['receipt_data' => null]);
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'An error occurred')));

    expect(dispatchMessage($connection, json_encode($payload, JSON_THROW_ON_ERROR)))->toBeNull();

    unlink($payload['printer_settings']['printer_name']);
});

test('malformed JSON sends exactly one invalid JSON response', function () {
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'Invalid JSON payload')));

    expect(dispatchMessage($connection, '{invalid json'))->toBeNull();
});
