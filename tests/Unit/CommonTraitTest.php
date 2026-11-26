<?php

declare(strict_types=1);

namespace Tests\Unit;

use Darkterminal\EscposPrinterServer\Utils\CommonTrait;

test('toIDR formats an integer with thousand separators', function () {
    $helper = new class
    {
        use CommonTrait;
    };

    expect($helper->toIDR(90000))->toBe('90.000');
});

test('toIDR can prefix the formatted amount', function () {
    $helper = new class
    {
        use CommonTrait;
    };

    expect($helper->toIDR(90000, true))->toBe('Rp. 90.000');
});

test('makeAlignText fits two columns in the requested width', function () {
    $helper = new class
    {
        use CommonTrait;
    };

    $aligned = $helper->makeAlignText('Total', '90.000', 40);
    $lines = explode("\n", $aligned);

    expect(str_ends_with($aligned, "\n"))->toBeTrue()
        ->and(strlen($lines[0]))->toBe(40);
});

test('makeWrapText returns at least one line ending with a newline', function () {
    $helper = new class
    {
        use CommonTrait;
    };

    $wrapped = $helper->makeWrapText('Kopi Robusta 250gr', 'Rp. 90.000', 'epson');

    expect($wrapped)->not->toBeEmpty()
        ->and(str_ends_with($wrapped, "\n"))->toBeTrue();
});
