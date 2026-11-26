<?php

declare(strict_types=1);

namespace Tests\Unit;

test('argv2assoc returns an empty array for no arguments', function () {
    expect(argv2assoc([]))->toBe([]);
});

test('argv2assoc maps command, options and their values', function () {
    expect(argv2assoc(['start', '--role', 'ws', '-p', '1945']))->toBe([
        'start' => null,
        '--role' => 'ws',
        '-p' => '1945',
    ]);
});

test('argv2assoc maps consecutive flags to null', function () {
    expect(argv2assoc(['--flag', '--other']))->toBe([
        '--flag' => null,
        '--other' => null,
    ]);
});
