<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\Session\Session;

final class Csrf
{
    private const string SESSION_KEY = '_webkernel_csrf';

    private function __construct()
    {
    }

    public static function token(string $form_id = 'form'): string
    {
        self::validate_form_id($form_id);
        Session::start();
        $tokens = Session::get(self::SESSION_KEY, []);
        if (!is_array($tokens)) {
            $tokens = [];
        }
        if (!is_string($tokens[$form_id] ?? null)) {
            $tokens[$form_id] = bin2hex(random_bytes(32));
            Session::putReserved(self::SESSION_KEY, $tokens);
        }

        return $tokens[$form_id];
    }

    public static function field(string $form_id = 'form', string $field_name = '_token'): CsrfField
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_\[\]-]*\z/', $field_name) !== 1) {
            throw new InvalidArgumentException('Invalid CSRF field name.');
        }

        return new CsrfField($field_name, self::token($form_id), $form_id);
    }

    public static function verify(string $submitted_token, string $form_id = 'form'): bool
    {
        if ($submitted_token === '') {
            return false;
        }
        try {
            $expected = self::token($form_id);
        } catch (RuntimeException) {
            return false;
        }

        return hash_equals($expected, $submitted_token);
    }

    public static function rotate(string $form_id = 'form'): string
    {
        self::validate_form_id($form_id);
        Session::start();
        $tokens = Session::get(self::SESSION_KEY, []);
        if (!is_array($tokens)) {
            $tokens = [];
        }
        $tokens[$form_id] = bin2hex(random_bytes(32));
        Session::putReserved(self::SESSION_KEY, $tokens);

        return $tokens[$form_id];
    }

    /** Validates the current mutation request and applies a supported POST method override. */
    public static function protectRequest(): bool
    {
        unset(
            $GLOBALS['WEBKERNEL_RAW_REQUEST_BODY'],
            $GLOBALS['WEBKERNEL_PARSED_REQUEST_BODY'],
        );

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $form_id = $_POST['_csrf_id'] ?? 'form';
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $raw_request_body = null;
        $parsed_request_body = null;

        // Livewire submits its payload as JSON with the CSRF token in `_token`.
        // PHP leaves $_POST empty for JSON requests, so read that token before
        // routing while leaving the request body available to Request::capture().
        $content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if ($token === '' && str_contains(strtolower((string) $content_type), 'application/json')) {
            $content = file_get_contents('php://input');
            $raw_request_body = is_string($content) ? $content : '';
            $parsed_request_body = $raw_request_body !== '' ? json_decode($raw_request_body, true) : null;

            if (is_array($parsed_request_body)) {
                $form_id = $parsed_request_body['_csrf_id'] ?? $form_id;
                $token = $parsed_request_body['_token'] ?? '';
            }
        }

        if (! is_string($form_id) || ! is_string($token)) {
            return false;
        }
        try {
            if (! self::verify($token, $form_id)) {
                return false;
            }
        } catch (InvalidArgumentException|RuntimeException) {
            return false;
        }

        if ($method === 'POST' && isset($_POST['_method'])) {
            if (! is_string($_POST['_method'])) {
                return false;
            }
            $override = strtoupper($_POST['_method']);
            if (! in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return false;
            }
            $_SERVER['REQUEST_METHOD'] = $override;
        }

        if (is_string($raw_request_body)) {
            $GLOBALS['WEBKERNEL_RAW_REQUEST_BODY'] = $raw_request_body;

            if (is_array($parsed_request_body)) {
                $GLOBALS['WEBKERNEL_PARSED_REQUEST_BODY'] = $parsed_request_body;
            }
        }

        return true;
    }

    private static function validate_form_id(string $form_id): void
    {
        if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_.-]{0,63}\z/', $form_id) !== 1) {
            throw new InvalidArgumentException('Invalid CSRF form identifier.');
        }
    }
}
