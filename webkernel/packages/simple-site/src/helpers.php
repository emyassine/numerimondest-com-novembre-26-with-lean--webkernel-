<?php declare(strict_types=1);

if (! function_exists('simple_site')) {
    function simple_site(string $site_id, string $subpath = ''): string
    {
        $sites = config('simple-site.sites', []);
        $site = is_array($sites) ? ($sites[$site_id] ?? null) : null;
        if (! is_array($site) || ! is_string($site['prefix'] ?? null)) {
            throw new \InvalidArgumentException(sprintf('Simple site "%s" is not configured.', $site_id));
        }

        $prefix = rtrim($site['prefix'], '/');
        $path = trim($subpath, '/');
        if ($path === '') {
            return ($prefix === '' ? '' : $prefix).'/';
        }

        return $prefix.'/'.$path.(str_ends_with($subpath, '/') ? '/' : '');
    }
}
