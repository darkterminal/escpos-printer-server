<?php

declare(strict_types=1);

namespace Tests\Unit;

use Darkterminal\EscposPrinterServer\Support\Auth;

test('matching configured token authorizes any remote ip', function () {
    expect(Auth::authorize('secret-a', '10.0.0.5', 'secret-a'))->toBeTrue();
});

test('mismatched configured token is rejected', function () {
    expect(Auth::authorize('secret-a', '10.0.0.5', 'secret-b'))->toBeFalse();
});

test('loopback ipv4 is authorized without a configured token', function () {
    expect(Auth::authorize(null, '127.0.0.1', null))->toBeTrue();
});

test('loopback ipv6 is authorized without a configured token', function () {
    expect(Auth::authorize(null, '::1', null))->toBeTrue();
});

test('remote ip is rejected and empty configured token behaves like no token', function () {
    expect(Auth::authorize(null, '10.0.0.5', null))->toBeFalse();
    expect(Auth::authorize('', '10.0.0.5', null))->toBeFalse();
    expect(Auth::authorize('', '127.0.0.1', null))->toBeTrue();
});
