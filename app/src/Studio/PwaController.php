<?php declare(strict_types=1);

namespace App\Studio;

use JsonException;
use Webkernel\Config\Config;
use Webkernel\HttpFoundation\Response;

final class PwaController
{
    /** @throws JsonException */
    public static function manifest(): Response
    {
        $config = Config::get('pwa', []);
        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return new Response('PWA is not enabled.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $manifest = [];
        foreach (['id', 'name', 'short_name', 'description', 'start_url', 'scope', 'display', 'orientation', 'background_color', 'theme_color', 'lang', 'icons'] as $key) {
            if (array_key_exists($key, $config)) {
                $manifest[$key] = $config[$key];
            }
        }

        return new Response(json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public static function serviceWorker(): Response
    {
        $source = base_path('app/resources/js/pwa-service-worker.js');
        $contents = is_file($source) ? file_get_contents($source) : false;
        if (! is_string($contents)) {
            return new Response('Service worker source is unavailable.', 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new Response($contents, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
