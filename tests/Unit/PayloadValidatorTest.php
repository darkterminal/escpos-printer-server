<?php

declare(strict_types=1);

namespace Tests\Unit;

use Darkterminal\EscposPrinterServer\Support\PayloadValidator;
use InvalidArgumentException;

function validPayload(): array
{
    return [
        'from' => 'posclient',
        'printer_name' => 'IW-8001',
        'printer_settings' => [
            'interface' => 'ethernet',
            'printer_host' => '192.168.1.150',
            'printer_port' => 9100,
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
}

test('README minimal payload validates and numeric strings become numbers', function () {
    $payload = validPayload();
    $payload['printer_settings']['printer_port'] = '9100';
    $payload['receipt_data']['transaction']['total_transaction'] = '45000';
    $payload['receipt_data']['cart'][0]['price'] = '45000.0';
    $payload['receipt_data']['cart'][0]['qty'] = '2';
    $payload['receipt_data']['cart'][0]['sub_total'] = '90000';

    $validated = PayloadValidator::validate($payload);

    expect($validated['printer_settings']['printer_port'])->toBeInt()->toBe(9100)
        ->and($validated['receipt_data']['transaction']['total_transaction'])->toBeInt()->toBe(45000)
        ->and($validated['receipt_data']['cart'][0]['price'])->toBeInt()->toBe(45000)
        ->and($validated['receipt_data']['cart'][0]['qty'])->toBeInt()->toBe(2)
        ->and($validated['receipt_data']['cart'][0]['sub_total'])->toBeInt()->toBe(90000);

    $fractional = validPayload();
    $fractional['receipt_data']['transaction']['total_transaction'] = '45000.5';
    $fractionalValidated = PayloadValidator::validate($fractional);

    expect($fractionalValidated['receipt_data']['transaction']['total_transaction'])->toBeFloat()->toBe(45000.5);
});

test('missing receipt_data for posclient and testprinter throws', function () {
    foreach (['posclient', 'testprinter'] as $from) {
        $payload = validPayload();
        $payload['from'] = $from;
        unset($payload['receipt_data']);

        expect(fn () => PayloadValidator::validate($payload))
            ->toThrow(InvalidArgumentException::class, 'receipt_data');
    }
});

test('receipt_data is optional for pullcashdrawer and checkprinter', function () {
    foreach (['pullcashdrawer', 'checkprinter'] as $from) {
        $payload = validPayload();
        $payload['from'] = $from;
        unset($payload['receipt_data']);

        $validated = PayloadValidator::validate($payload);

        expect($validated['from'])->toBe($from)
            ->and($validated['receipt_data'])->toBe([]);
    }
});

test('missing total_transaction throws with the field path', function () {
    $payload = validPayload();
    unset($payload['receipt_data']['transaction']['total_transaction']);

    expect(fn () => PayloadValidator::validate($payload))
        ->toThrow(InvalidArgumentException::class, 'receipt_data.transaction.total_transaction');
});

test('empty cart throws', function () {
    $payload = validPayload();
    $payload['receipt_data']['cart'] = [];

    expect(fn () => PayloadValidator::validate($payload))
        ->toThrow(InvalidArgumentException::class, 'receipt_data.cart');
});

test('unknown template and interface throw', function () {
    $badTemplate = validPayload();
    $badTemplate['printer_settings']['template'] = 'thermal';

    expect(fn () => PayloadValidator::validate($badTemplate))
        ->toThrow(InvalidArgumentException::class, 'printer_settings.template');

    $badInterface = validPayload();
    $badInterface['printer_settings']['interface'] = 'bluetooth';

    expect(fn () => PayloadValidator::validate($badInterface))
        ->toThrow(InvalidArgumentException::class, 'printer_settings.interface');
});

test('extra keys survive validation unchanged', function () {
    $payload = validPayload();
    $payload['promo'] = ['Beli 2 gratis 1 untuk produk tertentu'];
    $payload['auth_token'] = 'secret-token';
    $payload['printer_settings']['custom_language']['operator'] = 'Kasir';
    $payload['receipt_data']['payment'] = ['method' => 'cash'];

    $validated = PayloadValidator::validate($payload);

    expect($validated['promo'])->toBe(['Beli 2 gratis 1 untuk produk tertentu'])
        ->and($validated['auth_token'])->toBe('secret-token')
        ->and($validated['printer_settings']['custom_language']['operator'])->toBe('Kasir')
        ->and($validated['receipt_data']['payment'])->toBe(['method' => 'cash']);
});
