<?php

declare(strict_types=1);

namespace Tests\Feature;

use Darkterminal\EscposPrinterServer\Server;

$tempDir = null;

beforeEach(function () use (&$tempDir): void {
    $tempDir = sys_get_temp_dir().'/eps-config-'.bin2hex(random_bytes(4));
});

afterEach(function () use (&$tempDir): void {
    if (! is_string($tempDir) || ! is_dir($tempDir)) {
        return;
    }

    foreach (glob($tempDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    rmdir($tempDir);
});

test('constructor bootstraps the default config in an injected directory', function () use (&$tempDir): void {
    new Server('127.0.0.1', 1945, $tempDir);

    $configFile = $tempDir.DIRECTORY_SEPARATOR.'printer-settings.json';

    expect(file_exists($configFile))->toBeTrue();

    $config = json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);

    expect($config['printer_settings']['interface'])->toBe('ethernet')
        ->and($config['printer_settings']['template'])->toBe('epson');
});

test('repeated construction is idempotent', function () use (&$tempDir): void {
    new Server('127.0.0.1', 1945, $tempDir);
    $configFile = $tempDir.DIRECTORY_SEPARATOR.'printer-settings.json';
    $firstContents = file_get_contents($configFile);

    new Server('127.0.0.1', 1945, $tempDir);

    expect(file_get_contents($configFile))->toBe($firstContents);
});

test('repeated construction does not overwrite a modified config', function () use (&$tempDir): void {
    new Server('127.0.0.1', 1945, $tempDir);
    $configFile = $tempDir.DIRECTORY_SEPARATOR.'printer-settings.json';
    $config = json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);
    $config['printer_settings']['printer_host'] = '10.0.0.42';
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

    new Server('127.0.0.1', 1945, $tempDir);

    $reloaded = json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);

    expect($reloaded['printer_settings']['printer_host'])->toBe('10.0.0.42');
});

test('default constructor keeps its backward compatible signature', function (): void {
    $repoConfigDir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config';
    $repoConfigFile = $repoConfigDir.DIRECTORY_SEPARATOR.'printer-settings.json';
    $configFileExisted = file_exists($repoConfigFile);

    try {
        $server = new Server('127.0.0.1', 1945);

        expect($server)->toBeInstanceOf(Server::class);
    } finally {
        if (! $configFileExisted && is_file($repoConfigFile)) {
            unlink($repoConfigFile);
        }
        if (is_dir($repoConfigDir) && count(scandir($repoConfigDir)) === 2) {
            rmdir($repoConfigDir);
        }
    }
});
