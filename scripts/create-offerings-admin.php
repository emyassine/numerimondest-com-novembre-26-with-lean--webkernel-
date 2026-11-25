<?php declare(strict_types=1);

require dirname(__DIR__).'/internal/dependencies/autoload.php';

$target = dirname(__DIR__).'/config/offerings-admin.php';
if (is_file($target) && !in_array('--replace', $argv, true)) {
    fwrite(STDERR, "An offerings admin already exists. Pass --replace to reset its password.\n");
    exit(1);
}
if (!function_exists('readline') || !stream_isatty(STDIN)) {
    fwrite(STDERR, "Run this command in an interactive terminal.\n");
    exit(1);
}
$username = trim((string) readline('Admin username: '));
if (!preg_match('/\A[a-zA-Z0-9._@-]{3,80}\z/', $username)) {
    fwrite(STDERR, "Username must be 3–80 characters using letters, digits, dot, underscore, @, or hyphen.\n");
    exit(1);
}
$prompt = static function (string $label): string {
    fwrite(STDOUT, $label);
    $setEcho = static function (string $mode): void {
        $process = proc_open(['stty', $mode], [
            0 => ['file', '/dev/tty', 'r'], 1 => ['file', '/dev/tty', 'w'], 2 => ['file', '/dev/tty', 'w'],
        ], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Could not configure terminal password input.');
        $status = proc_close($process);
        if ($status !== 0) throw new RuntimeException('Could not configure terminal password input.');
    };
    $setEcho('-echo');
    try { $value = trim((string) fgets(STDIN)); }
    finally { $setEcho('echo'); fwrite(STDOUT, "\n"); }
    return $value;
};
$password = $prompt('Password (minimum 14 characters): ');
$confirmation = $prompt('Confirm password: ');
if (strlen($password) < 14 || !hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Password must be at least 14 characters and both entries must match.\n");
    exit(1);
}
$directory = dirname($target);
if (!is_dir($directory) || !is_writable($directory)) {
    fwrite(STDERR, "The config directory must exist and be writable by this command.\n");
    exit(1);
}
$config = "<?php declare(strict_types=1);\n\nreturn ".var_export([
    'username' => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
], true).";\n";
$temporary = $target.'.'.bin2hex(random_bytes(6)).'.tmp';
if (file_put_contents($temporary, $config, LOCK_EX) === false || !chmod($temporary, 0600) || !rename($temporary, $target)) {
    @unlink($temporary);
    fwrite(STDERR, "Could not install the admin credentials file.\n");
    exit(1);
}
fwrite(STDOUT, 'Offerings admin credentials saved to config/offerings-admin.php with mode 0600.'.PHP_EOL);
