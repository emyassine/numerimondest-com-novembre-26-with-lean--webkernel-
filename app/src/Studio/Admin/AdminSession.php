<?php declare(strict_types=1);

namespace App\Studio\Admin;

use Webkernel\Session\Session;

/** Keeps admin sessions isolated from public-site sessions. */
final class AdminSession
{
    public static function configure(): void
    {
        if (session_status() !== PHP_SESSION_NONE) return;
        Session::configure([
            'cookie' => 'NM_ADMIN_SESSID',
            'save_path' => base_path('runtime/sessions/admin'),
            'lifetime' => 480,
            'same_site' => 'Strict',
            'http_only' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
}
