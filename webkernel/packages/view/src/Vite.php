<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use RuntimeException;

final class Vite
{
    public function __invoke(string|array $entries, ?string $buildDirectory = 'build'): HtmlString
    {
        $entries = is_array($entries) ? $entries : [$entries];
        $base = function_exists('public_path') ? public_path($buildDirectory ?? 'build') : getcwd().'/public/'.($buildDirectory ?? 'build');
        $hotFile = dirname($base).'/hot';
        if (is_file($hotFile)) {
            $server = trim((string) file_get_contents($hotFile));
            $tags = '<script type="module" src="'.htmlspecialchars($server.'/@vite/client', ENT_QUOTES).'" ></script>';
            foreach ($entries as $entry) { $tags .= '<script type="module" src="'.htmlspecialchars($server.'/'.ltrim($entry, '/'), ENT_QUOTES).'" ></script>'; }
            return new HtmlString($tags);
        }
        $manifestPath = $base.'/manifest.json';
        if (! is_file($manifestPath)) { throw new RuntimeException("Vite manifest not found at [{$manifestPath}]."); }
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $tags = ''; $seen = [];
        foreach ($entries as $entry) {
            if (! isset($manifest[$entry]['file'])) { throw new RuntimeException("Vite entry [{$entry}] is missing from the manifest."); }
            foreach ($manifest[$entry]['css'] ?? [] as $css) { if (! isset($seen[$css])) { $seen[$css] = true; $tags .= '<link rel="stylesheet" href="/'.trim($buildDirectory ?? 'build', '/').'/'.htmlspecialchars($css, ENT_QUOTES).'">'; } }
            $file = htmlspecialchars($manifest[$entry]['file'], ENT_QUOTES);
            $tags .= '<script type="module" src="/'.trim($buildDirectory ?? 'build', '/').'/'.$file.'"></script>';
        }
        return new HtmlString($tags);
    }
}
