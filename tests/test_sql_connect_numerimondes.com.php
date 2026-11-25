<?php declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot.'/account/application/config/app-config.php';
define('BASEPATH', $projectRoot.'/account/system/');

if (!is_file($configPath)) {
    fwrite(STDERR, "Database config not found: {$configPath}\n");
    exit(2);
}

require $configPath;

$requiredConstants = ['APP_DB_HOSTNAME', 'APP_DB_USERNAME', 'APP_DB_PASSWORD', 'APP_DB_NAME'];
foreach ($requiredConstants as $constant) {
    if (!defined($constant) || !is_string(constant($constant)) || constant($constant) === '') {
        fwrite(STDERR, "Database config is missing {$constant}.\n");
        exit(2);
    }
}

if (!function_exists('mysqli_init')) {
    fwrite(STDERR, "The PHP mysqli extension is not loaded. Enable it for this PHP CLI version.\n");
    exit(2);
}

mysqli_report(MYSQLI_REPORT_OFF);
$connection = mysqli_init();
if (!$connection) {
    fwrite(STDERR, "Could not initialize mysqli.\n");
    exit(2);
}

$timeoutSeconds = 5;
$connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeoutSeconds);
$hostOverride = getenv('NM_TEST_DB_HOSTNAME');
$host = is_string($hostOverride) && $hostOverride !== '' ? $hostOverride : APP_DB_HOSTNAME;
$portOverride = getenv('NM_TEST_DB_PORT');
$port = is_string($portOverride) && ctype_digit($portOverride)
    ? (int) $portOverride
    : (defined('APP_DB_PORT') ? (int) APP_DB_PORT : 3306);

if (!$connection->real_connect($host, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME, $port)) {
    fwrite(STDERR, sprintf(
        "Database connection failed to %s:%d for database %s (MySQL %d): %s\n",
        $host,
        $port,
        APP_DB_NAME,
        $connection->connect_errno,
        $connection->connect_error,
    ));
    exit(1);
}

printf("Database connection succeeded to %s:%d for database %s.\n", $host, $port, APP_DB_NAME);
$connection->close();
