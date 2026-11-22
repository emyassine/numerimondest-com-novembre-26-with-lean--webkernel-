<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Actions;

use RuntimeException;
use Webkernel\Lifecycle\StandardLCAction;
use Webkernel\SimpleSite\Site\PageDiscovery;
use Webkernel\SimpleSite\Site\SiteFeedGenerator;

/** Publishes Simple Site's crawler and subscription feeds after Composer autoload generation. */
final class GenerateSiteFeedsAction extends StandardLCAction
{
    protected function run(): void
    {
        $root = base_path();
        $simpleSiteFile = $root.'/config/simple-site.php';
        $appConfigFile = $root.'/config/app.php';
        if (! is_file($simpleSiteFile) || ! is_file($appConfigFile)) {
            return;
        }

        $simpleSiteConfig = require $simpleSiteFile;
        $appConfig = require $appConfigFile;
        $sites = is_array($simpleSiteConfig) ? ($simpleSiteConfig['sites'] ?? []) : [];
        $baseUrl = is_array($appConfig) ? rtrim((string) ($appConfig['url'] ?? ''), '/') : '';
        if (! is_array($sites) || $sites === [] || $baseUrl === '') {
            return;
        }

        $public = $root.'/public';
        if (! is_dir($public) && ! mkdir($public, 0775, true) && ! is_dir($public)) {
            throw new RuntimeException('Unable to create the public directory for Simple Site feeds.');
        }

        $sites = array_filter($sites, 'is_array');
        $this->publish($public.'/sitemap.xml', SiteFeedGenerator::sitemap($sites, $baseUrl));

        $defaultSite = null;
        foreach ($sites as $site) {
            $prefix = trim((string) ($site['prefix'] ?? ''), '/');
            if ($prefix !== '' && preg_match('#\A[a-zA-Z0-9_/-]+\z#', $prefix) !== 1) {
                throw new RuntimeException('A Simple Site prefix cannot be used as a public feed path.');
            }
            if (($site['default_feed'] ?? false) === true || ($prefix === '' && $defaultSite === null)) {
                $defaultSite = $site;
            }

            $feedPath = $public.($prefix === '' ? '' : '/'.$prefix).'/rss.xml';
            $feedDirectory = dirname($feedPath);
            if (! is_dir($feedDirectory) && ! mkdir($feedDirectory, 0775, true) && ! is_dir($feedDirectory)) {
                throw new RuntimeException('Unable to create a localized Simple Site feed directory.');
            }
            $this->publish($feedPath, SiteFeedGenerator::rss([$site], $baseUrl));
        }

        if (is_array($defaultSite) && trim((string) ($defaultSite['prefix'] ?? ''), '/') !== '') {
            $this->publish($public.'/rss.xml', SiteFeedGenerator::rss([$defaultSite], $baseUrl));
        }
    }

    private function publish(string $path, string $contents): void
    {
        if (is_file($path) && file_get_contents($path) === $contents) {
            return;
        }

        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (file_put_contents($temporary, $contents, LOCK_EX) === false || ! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish Simple Site feed: '.basename($path));
        }
    }
}
