<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\ServerCommand;

final readonly class Status
{
    public function __construct(
        public string $php_version,
        public bool $opcache,
        public bool $jit,
        public string $jit_mode,
        public string $jit_buffer,
    ) {
    }

    public static function inspect(?string $sapi = null): self
    {
        $sapi ??= PHP_SAPI;
        $extension_loaded = extension_loaded('Zend OPcache');
        $cli = $sapi === 'cli' || $sapi === 'phpdbg';
        $opcache = $extension_loaded && self::ini_on($cli ? 'opcache.enable_cli' : 'opcache.enable');
        $jit_mode = (string) ini_get('opcache.jit');
        $jit_buffer = (string) ini_get('opcache.jit_buffer_size');
        $jit = false;

        if ($sapi === PHP_SAPI && $opcache && function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if (is_array($status)) {
                $opcache = (bool) ($status['opcache_enabled'] ?? $opcache);
                $jit_status = $status['jit'] ?? null;
                if (is_array($jit_status)) {
                    $jit = (bool) ($jit_status['on'] ?? false);
                    if (isset($jit_status['buffer_size']) && is_numeric($jit_status['buffer_size'])) {
                        $jit_buffer = (string) (int) $jit_status['buffer_size'];
                    }
                }
            }
        } elseif ($opcache) {
            $mode = strtolower(trim($jit_mode));
            $buffer = strtolower(trim($jit_buffer));
            $jit = $mode !== '' && ! in_array($mode, ['0', 'disable', 'off', 'no'], true)
                && $buffer !== '' && ! in_array($buffer, ['0', '0b', '0k', '0m', '0g'], true);
        }

        return new self(PHP_VERSION, $opcache, $jit, $jit_mode !== '' ? $jit_mode : 'disable', $jit_buffer);
    }

    private static function ini_on(string $key): bool
    {
        $value = ini_get($key);
        if ($value === false) {
            return false;
        }
        $value = strtolower(trim($value));

        return $value !== '' && ! in_array($value, ['0', 'off', 'false'], true);
    }
}
