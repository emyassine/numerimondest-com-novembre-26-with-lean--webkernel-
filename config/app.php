<?php declare(strict_types=1);

$appUrl = getenv('APP_URL');
if (! is_string($appUrl) || trim($appUrl) === '') {
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $requestHost = preg_replace('/:\\d+\\z/', '', $requestHost) ?? '';
    $appUrl = in_array($requestHost, ['numerimondes.com', 'www.numerimondes.com'], true)
        ? 'https://numerimondes.com/'
        : 'http://127.0.0.1:8000/';
}

return [
    'name' => 'Numerimondes',
    'url' => $appUrl,
    'env' => 'local',
    'debug' => true,
];
