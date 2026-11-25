<?php declare(strict_types=1);

/** Temporarily isolates local administrator credentials during tests. */
function beginAdminCredentialsTest(string $root, bool $configured): void
{
    $path = $root.'/config/offerings-admin.php';
    $hadOriginal = is_file($path);
    $backupPath = null;
    $originalMode = $hadOriginal ? (fileperms($path) & 0777) : 0600;
    if ($hadOriginal) {
        $backupPath = tempnam(sys_get_temp_dir(), 'nm-admin-config-');
        if (!is_string($backupPath) || !copy($path, $backupPath)) throw new RuntimeException('Could not protect the local admin config for tests.');
    }

    register_shutdown_function(static function () use ($path, $hadOriginal, $backupPath, $originalMode): void {
        if (is_file($path)) unlink($path);
        if ($hadOriginal && is_string($backupPath) && is_file($backupPath)) {
            if (!copy($backupPath, $path)) error_log('Could not restore the local admin config after tests.');
            else chmod($path, $originalMode);
            unlink($backupPath);
        }
    });

    if ($configured) {
        $data = ['username' => 'test-admin', 'password_hash' => password_hash('test-password-for-suite', PASSWORD_DEFAULT)];
        if (file_put_contents($path, "<?php declare(strict_types=1);\n\nreturn ".var_export($data, true).";\n", LOCK_EX) === false || !chmod($path, 0600)) {
            throw new RuntimeException('Could not install isolated admin credentials for tests.');
        }
    } elseif (is_file($path) && !unlink($path)) {
        throw new RuntimeException('Could not isolate the first-admin setup test from local credentials.');
    }
}
