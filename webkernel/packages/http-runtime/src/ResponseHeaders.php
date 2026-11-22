<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Http;

use InvalidArgumentException;

/** Sends configured browser security headers before the application response. */
final class ResponseHeaders
{
    private function __construct() {}

    /** @param array<string, mixed> $configuration */
    public static function apply(array $configuration, bool $secure_request): string
    {
        $nonce = base64_encode(random_bytes(18));

        if (($configuration['remove_powered_by'] ?? true) === true) {
            header_remove('X-Powered-By');
        }

        foreach (self::build($configuration, $secure_request, $nonce) as $name => $value) {
            header($name.': '.$value, true);
        }

        return $nonce;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, string>
     */
    public static function build(array $configuration, bool $secure_request, string $nonce): array
    {
        if (isset($configuration['remove_powered_by']) && ! is_bool($configuration['remove_powered_by'])) {
            throw new InvalidArgumentException('The remove_powered_by setting must be a boolean.');
        }

        if (preg_match('/\A[A-Za-z0-9+\/_=-]{16,}\z/', $nonce) !== 1) {
            throw new InvalidArgumentException('A valid per-response CSP nonce is required.');
        }

        $header_settings = [
            'content_security_policy' => 'Content-Security-Policy',
            'x_content_type_options' => 'X-Content-Type-Options',
            'x_frame_options' => 'X-Frame-Options',
            'referrer_policy' => 'Referrer-Policy',
            'permissions_policy' => 'Permissions-Policy',
        ];
        $headers = [];

        foreach ($header_settings as $key => $name) {
            $value = $configuration[$key] ?? null;
            if ($value === null || $value === false) {
                continue;
            }
            if (! is_string($value) || preg_match('/[\r\n]/', $value) === 1) {
                throw new InvalidArgumentException('Invalid configured value for '.$name.'.');
            }

            if ($key === 'content_security_policy') {
                if (! str_contains($value, '{nonce}')) {
                    throw new InvalidArgumentException('The content security policy must include the {nonce} placeholder.');
                }
                $value = str_replace('{nonce}', $nonce, $value);
            }

            $headers[$name] = $value;
        }

        $strict_transport_security = $configuration['strict_transport_security'] ?? null;
        if ($strict_transport_security !== null && $strict_transport_security !== false && ! is_string($strict_transport_security)) {
            throw new InvalidArgumentException('Invalid configured value for Strict-Transport-Security.');
        }
        if (is_string($strict_transport_security)) {
            if ($strict_transport_security === '' || preg_match('/[\r\n]/', $strict_transport_security) === 1) {
                throw new InvalidArgumentException('Invalid configured value for Strict-Transport-Security.');
            }
        }
        if ($secure_request && is_string($strict_transport_security)) {
            $headers['Strict-Transport-Security'] = $strict_transport_security;
        }

        return $headers;
    }
}
