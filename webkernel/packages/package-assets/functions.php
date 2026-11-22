<?php declare(strict_types=1);

if (! function_exists('webkernel_packages')) {
    /** Returns package metadata compiled by the development/build toolchain. */
    function webkernel_packages(): array
    {
        static $packages;
        static $snapshot;
        $manifest_path = base_path('runtime/cache/declarations/webkernel_packages.php');
        clearstatcache(true, $manifest_path);
        $stat = @stat($manifest_path);
        $current = $stat === false ? null : [(int) $stat['mtime'], (int) $stat['size'], (int) $stat['ctime'], (int) $stat['ino']];
        if (! is_array($packages) || $snapshot !== $current) {
            $manifest = is_file($manifest_path) ? require $manifest_path : [];
            $packages = is_array($manifest) ? $manifest : [];
            $snapshot = $current;
        }
        return $packages;
    }
}
