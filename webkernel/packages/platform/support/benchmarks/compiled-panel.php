<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

if ($argc < 2 || $argc > 3) {
    fwrite(STDERR, "Usage: php compiled-panel.php https://host/panel [warm-request-count]\n");
    exit(2);
}

$url = $argv[1];
$parts = parse_url($url);
$warm_request_count = isset($argv[2]) ? filter_var($argv[2], FILTER_VALIDATE_INT) : 200;
if (! is_array($parts)
    || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
    || ! is_string($parts['host'] ?? null)
    || ! is_int($warm_request_count)
    || $warm_request_count < 1
    || $warm_request_count > 2000) {
    fwrite(STDERR, "The URL or warm request count is invalid.\n");
    exit(2);
}

$headers = ['Accept: text/html', 'Connection: keep-alive'];
$cookie = getenv('WEBKERNEL_BENCHMARK_COOKIE');
if (is_string($cookie) && $cookie !== '') {
    $headers[] = 'Cookie: '.$cookie;
}
$authorization = getenv('WEBKERNEL_BENCHMARK_AUTHORIZATION');
if (is_string($authorization) && $authorization !== '') {
    $headers[] = 'Authorization: '.$authorization;
}
$curl = curl_init($url);
if ($curl === false) {
    throw new RuntimeException('Unable to initialize the HTTP benchmark client.');
}
$response_headers = [];
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$response_headers): int {
        $response_headers[] = trim($line);
        return strlen($line);
    },
]);

$request = static function () use ($curl, &$response_headers): array {
    $response_headers = [];
    $started_at = hrtime(true);
    $body = curl_exec($curl);
    $elapsed_ms = (hrtime(true) - $started_at) / 1e6;
    if (! is_string($body)) {
        throw new RuntimeException('Benchmark request failed: '.curl_error($curl));
    }
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $timings = [];
    foreach ($response_headers as $header) {
        if (preg_match('/\AServer-Timing:\s*(.*)\z/i', $header, $matches) !== 1) {
            continue;
        }
        foreach (explode(',', $matches[1]) as $entry) {
            if (preg_match('/\s*([A-Za-z0-9_-]+);dur=([0-9.]+)/', $entry, $timing) === 1) {
                $timings[$timing[1]] = (float) $timing[2];
            }
        }
    }
    return ['status' => $status, 'bytes' => strlen($body), 'elapsed_ms' => $elapsed_ms, 'timings' => $timings];
};

try {
    $first = $request();
    if ($first['status'] !== 200) {
        throw new RuntimeException('First-hit response was HTTP '.$first['status'].'; a successful full page is required.');
    }
    $samples = [];
    $timing_samples = [];
    for ($index = 0; $index < $warm_request_count; $index++) {
        $sample = $request();
        if ($sample['status'] !== 200) {
            throw new RuntimeException('Warm request '.($index + 1).' returned HTTP '.$sample['status'].'.');
        }
        $samples[] = $sample['elapsed_ms'];
        foreach ($sample['timings'] as $name => $duration) {
            $timing_samples[$name][] = $duration;
        }
    }
} finally {
    curl_close($curl);
}

$percentile = static function (array $values, float $percent): float {
    sort($values, SORT_NUMERIC);
    $index = max(0, (int) ceil(count($values) * $percent) - 1);
    return (float) $values[$index];
};

printf("target=%s\n", $url);
printf("php=%s benchmark_sapi=%s warm_opcache_state=target-specific\n", PHP_VERSION, PHP_SAPI);
printf("first_hit_status=%d first_hit_ms=%.3f response_bytes=%d\n", $first['status'], $first['elapsed_ms'], $first['bytes']);
foreach ($first['timings'] as $name => $duration) {
    printf("server_timing_first.%s_ms=%.3f\n", $name, $duration);
}
printf("warm_requests=%d p50_ms=%.3f p95_ms=%.3f\n", count($samples), $percentile($samples, 0.50), $percentile($samples, 0.95));
foreach ($timing_samples as $name => $values) {
    printf("server_timing.%s.p50_ms=%.3f\n", $name, $percentile($values, 0.50));
}
