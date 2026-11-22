<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts;

use Webkernel\Session\Session;

/** Resolves current identity and permissions from the server side on each check. */
final class Auth
{
    public static function user(): ?User
    {
        $id = Session::get('account_id');
        if (! is_int($id) || $id < 1) { return null; }
        $user = (new Accounts())->find($id);
        if ($user === null) { Session::forget('account_id'); }
        return $user;
    }

    public static function can(string $permission, ?string $module = null): bool
    {
        if ($permission === '' || $module !== null) { return false; }
        return self::user()?->can($permission) ?? false;
    }

    /** @param list<string> $permissions */
    public static function canAny(array $permissions, ?string $module = null): bool
    {
        if ($permissions === [] || $module !== null) { return false; }
        foreach ($permissions as $permission) { if (is_string($permission) && self::can($permission)) { return true; } }
        return false;
    }
}
