<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

//>
//> Healthy path: load configured autoload + validate the generated boot manifest.
//> Recovery path: rebuild declarations once through Composer, then re-check.

// ---------------------------------------------------------------------------
// Fast path - no class allocation when the configured autoload file exists.
// ---------------------------------------------------------------------------

$base_path = dirname(__DIR__);
$paths_config_file = $base_path . '/config/paths.php';
$paths = is_file($paths_config_file) ? require $paths_config_file : [];

if (!is_array($paths) || !isset($paths['autoload']) || !is_string($paths['autoload'])) {
    fwrite(STDERR, 'fast-boot: config/paths.php must define a string autoload path.' . PHP_EOL);
    exit(1);
}

$autoload = $base_path . '/' . $paths['autoload'];
if (is_file($autoload)) {
    require $autoload;

    if (PHP_SAPI === 'cli') {
        $boot_manifest = $paths['boot_manifest'] ?? null;
        if (!is_string($boot_manifest) || $boot_manifest === '' || str_starts_with($boot_manifest, '/') || str_contains($boot_manifest, '..')) {
            fwrite(STDERR, 'fast-boot: config/paths.php must define a valid boot_manifest path.' . PHP_EOL);
            exit(1);
        }

        $absolute_boot_manifest = $base_path . '/' . ltrim($boot_manifest, '/');
        if (!webkernel_fast_boot_artifacts_are_ready($absolute_boot_manifest, $base_path)) {
            if (getenv('WEBKERNEL_FAST_BOOT_REBUILD') === '1') {
                fwrite(STDERR, 'fast-boot: recursive declaration recovery was prevented; Composer did not publish a complete boot manifest.' . PHP_EOL);
                exit(1);
            }
            webkernel_fast_boot_rebuild_declarations($absolute_boot_manifest, $base_path);
            if (!webkernel_fast_boot_artifacts_are_ready($absolute_boot_manifest, $base_path)) {
                fwrite(STDERR, 'fast-boot: Composer completed, but the generated boot artifacts are still incomplete.' . PHP_EOL);
                exit(1);
            }
        }
    }

    return;
}

/** Checks only the compiler-published artifact list; it never scans source trees. */
function webkernel_fast_boot_artifacts_are_ready(string $manifest_path, string $base_path): bool
{
    if (!is_file($manifest_path)) {
        return false;
    }

    $manifest = require $manifest_path;
    if (!is_array($manifest) || !isset($manifest['artifacts']) || !is_array($manifest['artifacts'])) {
        return false;
    }

    foreach ($manifest['artifacts'] as $artifact) {
        if (!is_string($artifact) || $artifact === '' || str_contains($artifact, '..') || str_starts_with($artifact, '/')) {
            return false;
        }
        if (!is_file($base_path . '/' . $artifact)) {
            return false;
        }
    }

    return true;
}

/** Runs one bounded Composer rebuild. Composer does not invoke this fast-boot entry point. */
function webkernel_fast_boot_rebuild_declarations(string $manifest_path, string $base_path): void
{
    $lock_directory = dirname($manifest_path);
    if (!is_dir($lock_directory) && !@mkdir($lock_directory, 0775, true) && !is_dir($lock_directory)) {
        fwrite(STDERR, 'fast-boot: unable to create the declaration cache directory: ' . $lock_directory . PHP_EOL);
        exit(1);
    }

    $lock = @fopen($manifest_path . '.lock', 'c');
    if ($lock === false) {
        fwrite(STDERR, 'fast-boot: unable to create the declaration rebuild lock.' . PHP_EOL);
        exit(1);
    }

    $lock_deadline = microtime(true) + 10.0;
    while (!flock($lock, LOCK_EX | LOCK_NB)) {
        if (microtime(true) >= $lock_deadline) {
            fclose($lock);
            fwrite(STDERR, 'fast-boot: timed out waiting for another declaration rebuild.' . PHP_EOL);
            exit(1);
        }
        usleep(10000);
    }

    if (webkernel_fast_boot_artifacts_are_ready($manifest_path, $base_path)) {
        flock($lock, LOCK_UN);
        fclose($lock);
        return;
    }

    fwrite(STDERR, 'fast-boot: generated artifacts are missing; rebuilding declarations...' . PHP_EOL);
    $environment = getenv();
    if (!is_array($environment)) {
        $environment = [];
    }
    $environment['WEBKERNEL_FAST_BOOT_REBUILD'] = '1';

    $process = proc_open(
        ['composer', 'dump-autoload', '--no-interaction', '--no-ansi'],
        [0 => ['file', '/dev/null', 'r'], 1 => STDOUT, 2 => STDERR],
        $pipes,
        $base_path,
        $environment,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        flock($lock, LOCK_UN);
        fclose($lock);
        fwrite(STDERR, 'fast-boot: unable to start Composer.' . PHP_EOL);
        exit(1);
    }

    $deadline = microtime(true) + 60.0;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) >= $deadline) {
            proc_terminate($process, 15);
            usleep(100000);
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process, 9);
            }
            proc_close($process);
            flock($lock, LOCK_UN);
            fclose($lock);
            fwrite(STDERR, 'fast-boot: Composer declaration rebuild exceeded 60 seconds.' . PHP_EOL);
            exit(1);
        }
        usleep(10000);
    } while (true);

    $exit_code = proc_close($process);
    flock($lock, LOCK_UN);
    fclose($lock);
    if (!is_int($exit_code) || $exit_code !== 0) {
        exit(is_int($exit_code) && $exit_code > 0 ? $exit_code : 1);
    }
}

// --- Tier-2 + miss path - only reached when packagist/autoload.php is absent-

(new class($paths) {

    // State -----------------------------------------------------------------

    private string $base_path;
    private array $paths;
    private string $composer_phar_url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    private string $composer_sha256_url = 'https://getcomposer.org/download/latest-stable/composer.phar.sha256sum';
    private int $composer_timeout = 120;

    public function __construct(array $paths)
    {
        $this->base_path = dirname(__DIR__);
        $this->paths = $paths;
    }

    // versionning

    public function version(): string { return '0.12.0'; }

    // Entry point (fluent so callers can chain if needed) -------------------

    public function fast_boot(): static
    {
        if ($this->require_if_valid($this->paths['autoload'])) {
            return $this;
        }

        // Miss path.
        $this->handle_miss();

        return $this;
    }

    // -----------------------------------------------------------------------
    // Tier-2 require helper
    // -----------------------------------------------------------------------

    private function require_if_valid(mixed $rel): bool
    {
        if (!is_string($rel) || $rel === '' || str_contains($rel, '..')) {
            return false;
        }

        $abs = $this->base_path . '/' . $rel;

        if (!is_file($abs)) {
            return false;
        }

        require $abs;
        return true;
    }

    // -----------------------------------------------------------------------
    // Miss path
    // -----------------------------------------------------------------------

    private function handle_miss(): void
    {
        $candidates = $this->build_candidate_list();
        $rel        = $this->find_first_existing($candidates);

        if ($rel !== null) {
            require $this->base_path . '/' . $rel;
            return;
        }

        if (PHP_SAPI !== 'cli') {
            $this->fail_with_message('vendor autoload missing. Run: composer install');
        }

        $this->run_composer_install();

        $rel = $this->find_first_existing($candidates);

        if ($rel === null) {
            $this->fail_with_message('dependency installation failed. Run: composer install');
        }

        fwrite(STDERR, 'fast-boot: PHP dependencies installed.' . PHP_EOL);

        require $this->base_path . '/' . $rel;
    }

    // -----------------------------------------------------------------------
    // Candidate discovery
    // -----------------------------------------------------------------------

    private function build_candidate_list(): array
    {
        $candidates = [];

        $from_json = $this->read_vendor_dir_from_composer();
        if ($from_json !== null) {
            $candidates[$from_json] = true;
        }

        if (isset($this->paths['autoload']) && is_string($this->paths['autoload'])) {
            $candidates[$this->paths['autoload']] = true;
        }

        return array_keys($candidates);
    }

    private function read_vendor_dir_from_composer(): ?string
    {
        $composer_json = $this->base_path . '/composer.json';

        if (!is_file($composer_json)) {
            return null;
        }

        $raw = file_get_contents($composer_json);
        if ($raw === false) {
            $this->fail_with_message('unable to read composer.json');
        }

        $decoded = null;

        try {
            $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->fail_with_message('composer.json is not valid JSON: ' . $e->getMessage());
        }

        if (!is_array($decoded)) {
            $this->fail_with_message('composer.json is not an object.');
        }

        $vendor_dir = $decoded['config']['vendor-dir'] ?? dirname($this->paths['autoload']);

        if (!is_string($vendor_dir)) {
            $this->fail_with_message('invalid composer config.vendor-dir.');
        }

        return $this->normalize_vendor_dir($vendor_dir);
    }

    private function normalize_vendor_dir(string $vendor): ?string
    {
        $vendor = trim(strtr($vendor, '\\', '/'), '/');

        if ($vendor === '' || $vendor[0] === '/') {
            return null;
        }

        foreach (explode('/', $vendor) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $vendor . '/autoload.php';
    }

    private function find_first_existing(array $candidates): ?string
    {
        foreach ($candidates as $rel) {
            if (is_file($this->base_path . '/' . $rel)) {
                return $rel;
            }
        }
        return null;
    }

    // -----------------------------------------------------------------------
    // -----------------------------------------------------------------------
    // Composer bootstrap (CLI miss path only)
    // -----------------------------------------------------------------------

    private function run_composer_install(): void
    {
        fwrite(STDERR, 'fast-boot: installing PHP dependencies...' . PHP_EOL);

        $composer = $this->resolve_composer_argv();
        $tmp_phar = $composer['temporary_phar'];
        $exit_code     = 1;

        try {
            $pipes   = [];
            $environment = $this->clean_env();
            $environment['TMPDIR'] = $this->temporary_directory();
            $process = proc_open(
                [
                    ...$composer['arguments'],
                    'install',
                    '--working-dir=' . $this->base_path,
                    '--no-interaction',
                    '--no-ansi',
                ],
                [0 => STDIN, 1 => STDOUT, 2 => STDERR],
                $pipes,
                $this->base_path,
                $environment,
                ['bypass_shell' => true]
            );

            if ($process === false) {
                $this->fail_with_message('unable to start composer. Run: composer install');
            }

            $status    = proc_close($process);
            $exit_code = is_int($status) ? $status : 1;
        } finally {
            if ($tmp_phar !== null && is_file($tmp_phar)) {
                unlink($tmp_phar);
            }
        }

        if ($exit_code !== 0) {
            $this->fail_with_message('composer exited with code ' . $exit_code . '. Run: composer install');
        }
    }

    /** @return array{arguments: list<string>, temporary_phar: ?string} */
    private function resolve_composer_argv(): array
    {
        $bin = $this->find_composer_on_path();
        if ($bin !== null) {
            return ['arguments' => [$bin], 'temporary_phar' => null];
        }

        if (!ini_get('allow_url_fopen')) {
            $this->fail_with_message('allow_url_fopen is disabled. Run: composer install');
        }

        $phar_bytes = $this->download_composer_phar();
        $tmp_phar = $this->write_tmp_phar($phar_bytes);

        return ['arguments' => [PHP_BINARY, $tmp_phar], 'temporary_phar' => $tmp_phar];
    }

    private function find_composer_on_path(): ?string
    {
        $path_env = getenv('PATH');
        if (!is_string($path_env) || $path_env === '') {
            return null;
        }

        foreach (explode(PATH_SEPARATOR, $path_env) as $dir) {
            $bin = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'composer';
            if (is_file($bin) && is_executable($bin)) {
                return $bin;
            }
        }

        return null;
    }

    private function download_composer_phar(): string
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => $this->composer_timeout, 'follow_location' => 1],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $phar_bytes = file_get_contents($this->composer_phar_url, false, $ctx);
        $sha256_sum = file_get_contents($this->composer_sha256_url, false, $ctx);

        if ($phar_bytes === false || $phar_bytes === '' || $sha256_sum === false || $sha256_sum === '') {
            $this->fail_with_message('unable to download composer. Run: composer install');
        }

        $checksum_token = strtok(trim((string) $sha256_sum), " \t");
        if ($checksum_token === false) {
            $this->fail_with_message('composer.phar checksum line is empty.');
        }

        $checksum_token = (string) $checksum_token;
        $phar_bytes = (string) $phar_bytes;
        $expected = strtolower($checksum_token);
        $phar_trimmed = ltrim($phar_bytes);

        if (
            preg_match('/^[a-f0-9]{64}$/', $expected) !== 1 ||
            !hash_equals($expected, hash('sha256', $phar_bytes)) ||
            (!str_starts_with($phar_trimmed, '<?php') && !str_starts_with($phar_trimmed, '#!/usr/bin/env php'))
        ) {
            $this->fail_with_message('composer.phar checksum mismatch.');
        }

        return $phar_bytes;
    }

    private function write_tmp_phar(string $phar_bytes): string
    {
        $tmp_dir = $this->temporary_directory();

        $tmp = tempnam($tmp_dir, 'wkc');
        if ($tmp === false) {
            $this->fail_with_message('unable to create temp file in ' . $tmp_dir . '.');
        }

        $tmp_file = (string) $tmp;

        if (file_put_contents($tmp_file, $phar_bytes, LOCK_EX) === false) {
            $this->fail_with_message('unable to write temporary composer.phar.');
        }

        chmod($tmp_file, 0444);

        return $tmp_file;
    }

    private function temporary_directory(): string
    {
        $temporary_path = $this->paths['temporary'] ?? null;
        if (!is_string($temporary_path) || $temporary_path === '' || str_contains($temporary_path, "\0")) {
            $this->fail_with_message('config/paths.php must define a valid temporary path.');
        }
        $segments = explode('/', str_replace('\\', '/', $temporary_path));
        if (str_starts_with($temporary_path, '/') || in_array('..', $segments, true)) {
            $this->fail_with_message('configured temporary path must stay inside the application root.');
        }

        $directory = $this->base_path . '/' . trim($temporary_path, '/\\');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->fail_with_message('unable to create configured temporary directory: ' . $directory . '.');
        }
        if (!is_writable($directory)) {
            $this->fail_with_message('configured temporary directory is not writable: ' . $directory . '.');
        }

        return $directory;
    }

    private function clean_env(): array
    {
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        unset($env['COMPOSER_HOME']);
        return $env;
    }

    // Error helper ----------------------------------------------------------

    /** @return never */
    private function fail_with_message(string $message): never
    {
        fwrite(STDERR, 'fast-boot: ' . $message . PHP_EOL); exit(1);
    }

})->fast_boot();
