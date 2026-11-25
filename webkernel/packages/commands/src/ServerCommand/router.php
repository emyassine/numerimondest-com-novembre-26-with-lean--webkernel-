<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\Console\IncludeClock;

if (PHP_SAPI !== 'cli-server') {
    return;
}

$server_request_started_at = \hrtime(true);

$parsed = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode(is_string($parsed) && $parsed !== '' ? $parsed : '/');
$original_uri = $uri;
$docroot = $_SERVER['DOCUMENT_ROOT'] ?? '';
if (! is_string($docroot) || $docroot === '') {
    http_response_code(500);

    return true;
}
$real_docroot = realpath($docroot);
$front_controller = $docroot.'/index.php';
$mounted = false;
$mounts = json_decode((string) (getenv('WEBKERNEL_SERVER_MOUNTS') ?: '[]'), true);
if (is_array($mounts)) {
    usort($mounts, static fn (array $a, array $b): int => strlen((string) ($b['prefix'] ?? '')) <=> strlen((string) ($a['prefix'] ?? '')));
    foreach ($mounts as $mount) {
        if (!is_array($mount) || !is_string($mount['prefix'] ?? null) || !is_string($mount['root'] ?? null)) continue;
        $prefix = rtrim($mount['prefix'], '/');
        if ($uri !== $prefix && !str_starts_with($uri, $prefix.'/')) continue;
        $mounted_root = realpath($mount['root']);
        if (!is_string($mounted_root) || !is_dir($mounted_root) || !is_file($mounted_root.'/index.php')) break;
        $docroot = $mounted_root;
        $real_docroot = $mounted_root;
        $front_controller = $mounted_root.'/index.php';
        $mounted = true;
        $uri = substr($uri, strlen($prefix));
        $uri = $uri === '' ? '/' : $uri;
        $_SERVER['SCRIPT_NAME'] = $prefix.'/index.php';
        $_SERVER['PHP_SELF'] = $prefix.'/index.php';
        break;
    }
}
$file = $docroot.$uri;
$real_file = realpath($file);

if (
    $uri !== '/'
    && $real_docroot !== false
    && $real_file !== false
    && str_starts_with($real_file, rtrim($real_docroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
    && is_file($real_file)
) {
    $ext = strtolower(pathinfo($real_file, PATHINFO_EXTENSION));
    $static = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    if (isset($static[$ext])) {
        $modified_at = filemtime($real_file);
        $size = filesize($real_file);
        if (! is_int($modified_at) || ! is_int($size)) {
            http_response_code(404);

            return true;
        }
        $etag = '"'.hash('sha256', $modified_at.':'.$size.':'.(string) fileinode($real_file)).'"';
        $hashed_name = preg_match('/(?:^|[._-])[a-f0-9]{8,}(?:[._-]|$)/i', basename($real_file)) === 1;
        header('Content-Type: '.$static[$ext]);
        header('X-Content-Type-Options: nosniff');
        header('ETag: '.$etag);
        header('Cache-Control: public, max-age='.($hashed_name ? '31536000, immutable' : '0, must-revalidate'));
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);

            return true;
        }
        header('Content-Length: '.(string) $size);
        readfile($real_file);

        return true;
    }

    if (!$mounted) return false;
}

$root = str_replace('\\', '/', dirname($_SERVER['DOCUMENT_ROOT'] ?? $docroot));
$profile = ($_SERVER['WEBKERNEL_PROFILE_LIFECYCLE'] ?? getenv('WEBKERNEL_PROFILE_LIFECYCLE')) === '1';

$is_api = str_starts_with($uri, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'));

$classes_before = [];
if ($profile) {
    $autoload = $root.'/internal/dependencies/autoload.php';
    // nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename -- DOCUMENT_ROOT is server configuration; the appended path is fixed.
    if (! is_file($autoload)) {
        http_response_code(500);

        return true;
    }
    require_once $autoload;
    $classes_before = get_declared_classes();
    if (! function_exists('webkernel_profile_enter')) {
        function webkernel_profile_enter(string $file): void { IncludeClock::enter($file); }
        function webkernel_profile_leave(): void { IncludeClock::leave(); }
    }
    IncludeClock::start();
    IncludeClock::hook_autoloader();
    if ($front_controller !== ($_SERVER['DOCUMENT_ROOT'] ?? '').'/index.php') {
        chdir(dirname($front_controller));
    }
    IncludeClock::run_file($front_controller);
} else {
    if ($front_controller !== ($_SERVER['DOCUMENT_ROOT'] ?? '').'/index.php') {
        chdir(dirname($front_controller));
    }
    require $front_controller;
}

$status = http_response_code();
if (! is_int($status) || $status < 100) {
    $status = 200;
}

$client = ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1').':'.($_SERVER['REMOTE_PORT'] ?? '0');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['REQUEST_URI'] ?? $original_uri;
$server_request_ms = (\hrtime(true) - $server_request_started_at) / 1e6;
$render = defined('START_REQUEST')
    ? sprintf(' render=%.2f', (hrtime(true) - START_REQUEST) / 1e6)
    : '';

file_put_contents('php://stderr', sprintf(
    "%s [%d]: %s %s%s request=%.2f\n",
    $client,
    $status,
    $method,
    $path,
    $render,
    $server_request_ms,
));

if ($profile) {
    file_put_contents('php://stderr', 'webkernel-type '.($is_api ? 'API' : 'WEB')."\n");

    $prefix = $root.'/';
    $to_rel = static function (string $abs) use ($prefix): string {
        $abs = str_replace('\\', '/', $abs);

        return str_starts_with($abs, $prefix) ? substr($abs, strlen($prefix)) : $abs;
    };

    $timed = IncludeClock::stop();
    $relative = [];
    foreach ($timed as $abs => $row) {
        $rel = $to_rel($abs);
        $relative[$rel] = $row;
        file_put_contents('php://stderr', sprintf(
            "webkernel-include %.3f %.3f %.3f %s\n",
            $row['ms'],
            $row['run_ms'],
            $row['read_ms'],
            $rel,
        ));
    }

    $classes = [];
    foreach (get_declared_classes() as $class) {
        if (in_array($class, $classes_before, true)) {
            continue;
        }
        $classes[] = $class;
        file_put_contents('php://stderr', 'webkernel-class '.$class."\n");
    }

    $mem = memory_get_usage(true);
    file_put_contents('php://stderr', 'webkernel-mem '.$mem."\n");
    $render_ms = defined('START_REQUEST')
        ? (hrtime(true) - START_REQUEST) / 1e6
        : null;
    $json = json_encode(
        IncludeClock::report($relative, $classes, $mem, $render_ms),
        JSON_UNESCAPED_SLASHES,
    );
    if (is_string($json)) {
        file_put_contents('php://stderr', 'webkernel-profile-json '.$json."\n");
    }
}

return true;
