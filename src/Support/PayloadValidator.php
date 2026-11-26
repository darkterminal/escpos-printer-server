<?php

declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer\Support;

final class PayloadValidator
{
    /** @var list<string> */
    private const INTERFACES = ['cups', 'ethernet', 'linux-usb', 'smb', 'windows-usb', 'windows-lpt'];

    /**
     * Validate a websocket payload and return a normalized copy of it.
     *
     * Unknown keys are preserved so payload extensions remain compatible.
     *
     * @param array<mixed> $payload
     * @return array<mixed>
     * @throws \InvalidArgumentException
     */
    public static function validate(array $payload): array
    {
        $validated = $payload;

        if (!isset($payload['from']) || !is_string($payload['from']) || $payload['from'] === '') {
            throw new \InvalidArgumentException('Invalid payload: from must be a non-empty string');
        }

        if (!isset($payload['printer_name']) || !is_string($payload['printer_name']) || $payload['printer_name'] === '') {
            throw new \InvalidArgumentException('Invalid payload: printer_name must be a non-empty string');
        }

        if (!isset($payload['printer_settings']) || !is_array($payload['printer_settings'])) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings must be an array');
        }

        $settings = array_replace([
            'template' => 'epson',
            'printer_host' => '',
            'printer_port' => 9100,
            'pull_cash_drawer' => false,
            'line_feed_each_in_items' => 1,
            'more_new_line' => 0,
            'custom_print_header' => [],
            'custom_print_footer' => [],
            'custom_language' => [],
        ], $payload['printer_settings']);

        if (!isset($settings['interface']) || !is_string($settings['interface']) || !in_array($settings['interface'], self::INTERFACES, true)) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.interface must be one of ' . implode(', ', self::INTERFACES));
        }

        if (!isset($settings['template']) || !is_string($settings['template']) || !in_array($settings['template'], ['epson', 'custom'], true)) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.template must be epson or custom');
        }

        if (!is_string($settings['printer_host'])) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.printer_host must be a string');
        }

        $port = self::number($settings['printer_port'], 'printer_settings.printer_port');
        if (!is_int($port) || $port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.printer_port must be an integer between 1 and 65535');
        }
        $settings['printer_port'] = $port;

        if (!is_bool($settings['pull_cash_drawer'])) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.pull_cash_drawer must be a boolean');
        }

        foreach (['line_feed_each_in_items', 'more_new_line'] as $field) {
            $value = self::number($settings[$field], "printer_settings.$field");
            if (!is_int($value) || $value < 0) {
                throw new \InvalidArgumentException("Invalid payload: printer_settings.$field must be a non-negative integer");
            }
            $settings[$field] = $value;
        }

        foreach (['custom_print_header', 'custom_print_footer'] as $field) {
            if (!is_array($settings[$field])) {
                throw new \InvalidArgumentException("Invalid payload: printer_settings.$field must be an array of strings");
            }
            foreach ($settings[$field] as $line) {
                if (!is_string($line)) {
                    throw new \InvalidArgumentException("Invalid payload: printer_settings.$field must be an array of strings");
                }
            }
        }

        if (!is_array($settings['custom_language'])) {
            throw new \InvalidArgumentException('Invalid payload: printer_settings.custom_language must be an array');
        }

        $validated['printer_settings'] = $settings;

        if ($payload['from'] === 'posclient' || $payload['from'] === 'testprinter') {
            if (!array_key_exists('receipt_data', $payload)) {
                throw new \InvalidArgumentException('Invalid payload: receipt_data is required for ' . $payload['from']);
            }

            if (is_array($payload['receipt_data'])) {
                $receipt = $payload['receipt_data'];

                foreach (['app_name', 'full_name'] as $field) {
                    if (!isset($receipt[$field]) || !is_string($receipt[$field]) || $receipt[$field] === '') {
                        throw new \InvalidArgumentException("Invalid payload: receipt_data.$field must be a non-empty string");
                    }
                }

                if (!isset($receipt['transaction']) || !is_array($receipt['transaction'])) {
                    throw new \InvalidArgumentException('Invalid payload: receipt_data.transaction must be an array');
                }

                $transaction = $receipt['transaction'];

                foreach (['date_transaction', 'transaction'] as $field) {
                    if (!isset($transaction[$field]) || !is_string($transaction[$field]) || $transaction[$field] === '') {
                        throw new \InvalidArgumentException("Invalid payload: receipt_data.transaction.$field must be a non-empty string");
                    }
                }

                if (!array_key_exists('total_transaction', $transaction)) {
                    throw new \InvalidArgumentException('Invalid payload: receipt_data.transaction.total_transaction is required');
                }
                $transaction['total_transaction'] = self::number(
                    $transaction['total_transaction'],
                    'receipt_data.transaction.total_transaction'
                );

                if (!isset($receipt['cart']) || !is_array($receipt['cart']) || !array_is_list($receipt['cart']) || $receipt['cart'] === []) {
                    throw new \InvalidArgumentException('Invalid payload: receipt_data.cart must be a non-empty list');
                }

                $cart = [];
                foreach ($receipt['cart'] as $index => $item) {
                    $path = "receipt_data.cart.$index";
                    if (!is_array($item)) {
                        throw new \InvalidArgumentException("Invalid payload: $path must be an array");
                    }
                    if (!isset($item['item_name']) || !is_string($item['item_name'])) {
                        throw new \InvalidArgumentException("Invalid payload: $path.item_name must be a string");
                    }

                    foreach (['price', 'qty'] as $field) {
                        if (!array_key_exists($field, $item)) {
                            throw new \InvalidArgumentException("Invalid payload: $path.$field is required");
                        }
                        $item[$field] = self::number($item[$field], "$path.$field");
                    }

                    if (array_key_exists('sub_total', $item)) {
                        $item['sub_total'] = self::number($item['sub_total'], "$path.sub_total");
                    }

                    $cart[$index] = $item;
                }

                $receipt['transaction'] = $transaction;
                $receipt['cart'] = $cart;
                $validated['receipt_data'] = $receipt;
            }
        } elseif (!array_key_exists('receipt_data', $validated)) {
            $validated['receipt_data'] = [];
        }

        return $validated;
    }

    /**
     * Accept JSON numbers and numeric strings and return a normalized number.
     *
     * @throws \InvalidArgumentException
     */
    private static function number(mixed $value, string $path): int|float
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            $number = is_string($value) ? $value + 0 : $value;
            if (is_float($number) && !is_finite($number)) {
                throw new \InvalidArgumentException("Invalid payload: $path must be a finite number");
            }
            if (is_float($number) && $number === floor($number) && abs($number) < PHP_INT_MAX) {
                return (int) $number;
            }

            return $number;
        }

        throw new \InvalidArgumentException("Invalid payload: $path must be numeric");
    }
}
