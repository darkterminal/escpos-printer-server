<?php

declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\Server;
use Workerman\Connection\TcpConnection;

function authTestPrinterFile(): string
{
    $file = tempnam(sys_get_temp_dir(), 'escpos-auth-test-');
    unlink($file);

    return $file;
}

function authTestPayload(string $printerFile, array $overrides = []): array
{
    $payload = [
        'from' => 'posclient',
        'printer_name' => 'IW-8001',
        'printer_settings' => [
            'interface' => 'linux-usb',
            'printer_name' => $printerFile,
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

function dispatchAuthMessage(TcpConnection $connection, array $payload): void
{
    ob_start();
    try {
        (new Server)->onMessage($connection, json_encode($payload, JSON_THROW_ON_ERROR));
    } finally {
        ob_end_clean();
    }
}

beforeEach(function () {
    putenv('EPS_AUTH_TOKEN');
});

afterEach(function () {
    putenv('EPS_AUTH_TOKEN');
});

test('remote client without a configured token is rejected before printing', function () {
    $printerFile = authTestPrinterFile();
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('10.0.0.5');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'Unauthorized')));
    $connection->expects($this->once())->method('close');

    dispatchAuthMessage($connection, authTestPayload($printerFile));

    expect(file_exists($printerFile))->toBeFalse();
});

test('loopback client without a configured token is authorized', function () {
    $printerFile = authTestPrinterFile();
    $connection = $this->createMock(TcpConnection::class);
    $connection->method('getRemoteIp')->willReturn('127.0.0.1');
    $connection->expects($this->once())
        ->method('send')
        ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, '[SUCCESS]')));
    $connection->expects($this->never())->method('close');

    dispatchAuthMessage($connection, authTestPayload($printerFile));

    expect(file_exists($printerFile))->toBeTrue();
    unlink($printerFile);
});

describe('with a configured token', function () {
    beforeEach(function () {
        putenv('EPS_AUTH_TOKEN'.'='.'feature-token');
    });

    afterEach(function () {
        putenv('EPS_AUTH_TOKEN');
    });

    test('remote client with the correct token is authorized', function () {
        $printerFile = authTestPrinterFile();
        $connection = $this->createMock(TcpConnection::class);
        $connection->method('getRemoteIp')->willReturn('10.0.0.5');
        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, '[SUCCESS]')));
        $connection->expects($this->never())->method('close');

        dispatchAuthMessage($connection, authTestPayload($printerFile, ['auth_token' => 'feature-token']));

        expect(file_exists($printerFile))->toBeTrue();
        unlink($printerFile);
    });

    test('remote client with the wrong token is rejected before printing', function () {
        $printerFile = authTestPrinterFile();
        $connection = $this->createMock(TcpConnection::class);
        $connection->method('getRemoteIp')->willReturn('10.0.0.5');
        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(fn ($message) => is_string($message) && str_contains($message, 'Unauthorized')));
        $connection->expects($this->once())->method('close');

        dispatchAuthMessage($connection, authTestPayload($printerFile, ['auth_token' => 'wrong-token']));

        expect(file_exists($printerFile))->toBeFalse();
    });
});
