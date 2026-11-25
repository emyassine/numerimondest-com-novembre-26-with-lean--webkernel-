<?php declare(strict_types=1);

$appUrl = getenv('APP_URL');
if (! is_string($appUrl) || trim($appUrl) === '') {
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $requestHost = preg_replace('/:\\d+\\z/', '', $requestHost) ?? '';
    $appUrl = in_array($requestHost, ['numerimondes.com', 'www.numerimondes.com'], true)
        ? 'https://numerimondes.com/'
        : 'http://127.0.0.1:8000/';
}
$appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
$appEnvironment = getenv('APP_ENV');
if (!is_string($appEnvironment) || trim($appEnvironment) === '') {
    $appEnvironment = in_array($appHost, ['numerimondes.com', 'www.numerimondes.com'], true) ? 'production' : 'local';
}

return [
    'name' => 'Numerimondes',
    'url' => $appUrl,
    'env' => $appEnvironment,
    'debug' => in_array($appEnvironment, ['local', 'development', 'testing'], true),
];
