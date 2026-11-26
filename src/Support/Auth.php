<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer\Support;

final class Auth
{
    /**
     * Read the shared token from the process environment.
     */
    public static function token(): ?string
    {
        $token = getenv('EPS_AUTH_TOKEN');

        return $token === false || $token === '' ? null : $token;
    }

    /**
     * Authorize a request by shared token or loopback address.
     */
    public static function authorize(?string $configuredToken, string $remoteIp, ?string $providedToken): bool
    {
        if ($configuredToken !== null && $configuredToken !== '') {
            return hash_equals($configuredToken, (string) $providedToken);
        }

        return in_array($remoteIp, ['127.0.0.1', '::1'], true);
    }
}
