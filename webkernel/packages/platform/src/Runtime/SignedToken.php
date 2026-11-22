<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use JsonException;
use RuntimeException;

/** Issues and verifies compact HMAC tokens for stateless compiled-runtime requests. */
final class SignedToken
{
    /** @throws RuntimeException When the signing key is too short or payload encoding fails. */
    public static function issue(string $subject, int $acl_version, int $expires_at, string $key): string
    {
        self::validateKey($key);
        if ($subject === '' || $acl_version < 0 || $expires_at <= time()) {
            throw new RuntimeException('Signed runtime token claims are invalid.');
        }
        try {
            $payload = json_encode(
                ['sub' => $subject, 'acl' => $acl_version, 'exp' => $expires_at],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Signed runtime token payload could not be encoded.', previous: $exception);
        }
        $message = self::base64UrlEncode($payload);
        return $message.'.'.self::base64UrlEncode(hash_hmac('sha256', $message, $key, true));
    }

    /** @return array{sub: string, acl: int, exp: int}|null */
    public static function verify(string $token, string $key, ?int $now = null): array|null
    {
        self::validateKey($key);
        $parts = explode('.', $token);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        $signature = self::base64UrlDecode($parts[1]);
        if ($signature === null || ! hash_equals(hash_hmac('sha256', $parts[0], $key, true), $signature)) {
            return null;
        }
        $payload = self::base64UrlDecode($parts[0]);
        if ($payload === null) {
            return null;
        }
        try {
            $claims = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (! is_array($claims)
            || ! is_string($claims['sub'] ?? null)
            || $claims['sub'] === ''
            || ! is_int($claims['acl'] ?? null)
            || $claims['acl'] < 0
            || ! is_int($claims['exp'] ?? null)
            || $claims['exp'] <= ($now ?? time())) {
            return null;
        }

        return ['sub' => $claims['sub'], 'acl' => $claims['acl'], 'exp' => $claims['exp']];
    }

    private static function validateKey(string $key): void
    {
        if (strlen($key) < 32) {
            throw new RuntimeException('Compiled runtime signing keys must contain at least 32 bytes.');
        }
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|null
    {
        if (preg_match('/\A[A-Za-z0-9_-]+\z/', $value) !== 1) {
            return null;
        }
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        return is_string($decoded) ? $decoded : null;
    }
}
