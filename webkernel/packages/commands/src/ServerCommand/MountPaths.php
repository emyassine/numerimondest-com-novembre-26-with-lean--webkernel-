<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\ServerCommand;

final class MountPaths
{
    public static function path(string $request_target): string
    {
        $path = explode('?', $request_target, 2)[0];

        return rawurldecode($path === '' ? '/' : $path);
    }

    /** @param array<string, string> $mounts Sorted by longest prefix first */
    public static function match(string $request_target, array $mounts): ?string
    {
        $path = self::path($request_target);
        foreach ($mounts as $prefix => $directory) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $prefix;
            }
        }

        return null;
    }
}
