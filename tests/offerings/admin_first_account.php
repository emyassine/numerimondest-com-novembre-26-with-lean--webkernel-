<?php declare(strict_types=1);

$root = dirname(__DIR__, 2);
$mode = $argv[1] ?? null;
if ($mode === 'request') {
    putenv('APP_ENV=local');
    $_SERVER['REQUEST_URI'] = '/admin/login';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTPS'] = 'off';
    require $root.'/public/index.php';
    echo "\n__STATUS__".(string) http_response_code();
    exit;
}
if ($mode === 'create') {
    putenv('APP_ENV=local');
    require $root.'/internal/dependencies/autoload.php';
    \Webkernel\Session\Session::configure([
        'cookie' => 'NM_ADMIN_SESSID', 'save_path' => $root.'/runtime/sessions/admin-tests',
        'lifetime' => 480, 'same_site' => 'Strict', 'http_only' => true, 'secure' => false,
    ]);
    session_id('adminfirstsetupintegration');
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/login';
    $token = \Webkernel\Csrf::token();
    $sessionId = session_id();
    session_write_close();
    session_id($sessionId);
    $_COOKIE['NM_ADMIN_SESSID'] = $sessionId;
    if (!\Webkernel\Csrf::verify($token)) { fwrite(STDERR, 'Preflight CSRF verification failed for session '.$sessionId.PHP_EOL); exit(2); }
    $_POST = [
        'username' => 'first-admin', 'password' => 'sample-password-1234',
        'password_confirmation' => 'sample-password-1234', '_token' => $token, '_csrf_id' => 'form',
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/admin/setup';
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['SERVER_NAME'] = 'localhost';
    require $root.'/public/index.php';
    echo "\n__STATUS__".(string) http_response_code();
    exit;
}
if ($mode === 'login') {
    putenv('APP_ENV=local');
    require $root.'/internal/dependencies/autoload.php';
    \Webkernel\Session\Session::configure([
        'cookie' => 'NM_ADMIN_SESSID', 'save_path' => $root.'/runtime/sessions/admin-tests',
        'lifetime' => 480, 'same_site' => 'Strict', 'http_only' => true, 'secure' => false,
    ]);
    session_id('adminfirstloginintegration');
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/login';
    $token = \Webkernel\Csrf::token();
    $sessionId = session_id();
    session_write_close();
    session_id($sessionId);
    $_COOKIE['NM_ADMIN_SESSID'] = $sessionId;
    if (!\Webkernel\Csrf::verify($token)) { fwrite(STDERR, 'Login CSRF session could not be restored.'.PHP_EOL); exit(2); }
    $_POST = ['username' => 'first-admin', 'password' => 'sample-password-1234', '_token' => $token, '_csrf_id' => 'form'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/admin/login';
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['SERVER_NAME'] = 'localhost';
    require $root.'/public/index.php';
    echo "\n__STATUS__".(string) http_response_code();
    echo "\n__AUTH__".(\Webkernel\Session\Session::get('studio.offerings.admin_authenticated_at') !== null ? 'yes' : 'no');
    exit;
}
if ($mode === 'invalid') {
    putenv('APP_ENV=local');
    require $root.'/internal/dependencies/autoload.php';
    \Webkernel\Session\Session::configure([
        'cookie' => 'NM_ADMIN_SESSID', 'save_path' => $root.'/runtime/sessions/admin-tests',
        'lifetime' => 480, 'same_site' => 'Strict', 'http_only' => true, 'secure' => false,
    ]);
    session_id('adminfirstinvalidsetup');
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/login';
    $token = \Webkernel\Csrf::token();
    $sessionId = session_id();
    session_write_close();
    session_id($sessionId);
    $_COOKIE['NM_ADMIN_SESSID'] = $sessionId;
    if (!\Webkernel\Csrf::verify($token)) { fwrite(STDERR, 'Invalid-setup CSRF session could not be restored.'.PHP_EOL); exit(2); }
    $_POST = ['username' => 'first-admin', 'password' => 'short', 'password_confirmation' => 'short', '_token' => $token, '_csrf_id' => 'form'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/admin/setup';
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['SERVER_NAME'] = 'localhost';
    require $root.'/public/index.php';
    echo "\n__STATUS__".(string) http_response_code();
    exit;
}

require __DIR__.'/admin_test_support.php';
beginAdminCredentialsTest($root, false);
$credentialsPath = $root.'/config/offerings-admin.php';
$process = proc_open([PHP_BINARY, __FILE__, 'request'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) throw new RuntimeException('Could not start the admin setup page request.');
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) throw new RuntimeException('Admin setup page request failed: '.$errors);
[$html, $statusValue] = explode("\n__STATUS__", (string) $output, 2);
if ((int) $statusValue !== 200 || !str_contains($html, 'Create admin account') || !str_contains($html, 'action="/admin/setup"')
    || !str_contains($html, 'name="_token"') || !str_contains($html, 'password_confirmation')
    || str_contains($html, 'scripts/create-offerings-admin.php') || str_contains($html, '<?php echo')) {
    throw new RuntimeException('The first-admin page did not render the graphical account setup form.');
}

$process = proc_open([PHP_BINARY, __FILE__, 'invalid'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) throw new RuntimeException('Could not start the invalid setup submission request.');
fclose($pipes[0]);
$invalidOutput = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) throw new RuntimeException('Invalid setup submission failed: '.$errors);
[$invalidHtml, $invalidStatus] = explode("\n__STATUS__", (string) $invalidOutput, 2);
if ((int) $invalidStatus !== 200 || !str_contains($invalidHtml, 'value="first-admin"')
    || !str_contains($invalidHtml, 'Use a password with at least 12 characters.') || str_contains($invalidHtml, '<?php echo')) {
    throw new RuntimeException('Setup validation did not render the username and inline password error.');
}

$process = proc_open([PHP_BINARY, __FILE__, 'create'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) throw new RuntimeException('Could not start the first-admin submission request.');
fclose($pipes[0]);
$submission = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) throw new RuntimeException('First-admin submission request failed: '.$errors);
if (!str_ends_with(trim((string) $submission), '__STATUS__303')) throw new RuntimeException('The first-admin POST route did not redirect after successful creation. '.substr((string) $submission, -400));

$password = 'sample-password-1234';
try {
    $account = is_file($credentialsPath) ? require $credentialsPath : null;
    if (!is_array($account) || ($account['username'] ?? null) !== 'first-admin'
        || !password_verify($password, (string) ($account['password_hash'] ?? ''))) {
        throw new RuntimeException('Graphical first-admin route did not save the password hash.');
    }
    if ((fileperms($credentialsPath) & 0777) !== 0600) throw new RuntimeException('Admin credentials file permissions are not restricted to 0600.');
    $process = proc_open([PHP_BINARY, __FILE__, 'login'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Could not start the admin login request.');
    fclose($pipes[0]);
    $loginOutput = stream_get_contents($pipes[1]);
    $loginErrors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !str_ends_with(trim((string) $loginOutput), "__STATUS__303\n__AUTH__yes")) {
        throw new RuntimeException('The Webkernel-protected login POST did not authenticate: '.$loginErrors.' '.substr((string) $loginOutput, -400));
    }
} finally {
    if (is_file($credentialsPath)) unlink($credentialsPath);
}

echo "Graphical first-admin setup renders, saves a password hash, and signs in.\n";
