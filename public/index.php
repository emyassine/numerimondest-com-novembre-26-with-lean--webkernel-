<?php declare(strict_types=1);

header('X-Robots-Tag: noai, noimageai');
header('TDM-Reservation: 1');
header('TDM-Policy: https://numerimondes.com/privacy');

use Webkernel\Http;

$GLOBALS['webkernel_request_started_at'] = hrtime(true);
define('START_REQUEST', $GLOBALS['webkernel_request_started_at']);

$base_path = dirname(__DIR__);
$maintenance_file = $base_path.'/internal/maintenance.php';

if (is_file($maintenance_file)) {
    require $maintenance_file;
    return;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$route = parse_url($uri, PHP_URL_PATH);
$route = is_string($route) && $route !== '' ? $route : '/';

if ($route === '/healthz' || $route === '/ready') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Platform is Ready';
    return;
}

require $base_path.'/internal/fast-boot.php';

Http::run();
