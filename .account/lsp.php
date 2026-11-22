#!/usr/bin/env php
<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

final class LspDiagnostics
{
    private mixed $process;
    /** @var array{0: resource, 1: resource} */
    private array $pipes;
    private string $buffer = '';
    private int $next_request_id = 1;
    private bool $supports_pull_diagnostics = false;
    /** @var array<string, list<array<string, mixed>>> */
    private array $published_diagnostics = [];

    /**
     * @param list<string> $command
     * @param array<string, mixed> $settings
     * @throws RuntimeException
     */
    public function __construct(
        private readonly array $command,
        private readonly string $server_name,
        private readonly string $language_id,
        private readonly string $root_path,
        private readonly float $timeout,
        private readonly array $settings = [],
    ) {
        $pipes = [];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => STDERR,
        ];

        $process = proc_open(
            $this->command,
            $descriptors,
            $pipes,
            $this->root_path,
            null,
            ['bypass_shell' => true],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Could not start ' . $this->server_name . '.');
        }
        if (!isset($pipes[0], $pipes[1]) || !is_resource($pipes[0]) || !is_resource($pipes[1])) {
            proc_terminate($process);
            throw new RuntimeException($this->server_name . ' process streams were not created.');
        }

        $this->process = $process;
        $this->pipes = [0 => $pipes[0], 1 => $pipes[1]];
        stream_set_blocking($this->pipes[1], false);
    }

    /** @throws RuntimeException */
    public function start(): void
    {
        $initialize_response = $this->request('initialize', [
            'processId' => getmypid(),
            'rootUri' => $this->path_to_uri($this->root_path),
            'rootPath' => $this->root_path,
            'capabilities' => [
                'workspace' => [
                    'configuration' => true,
                    'workspaceFolders' => true,
                ],
                'textDocument' => [
                    'publishDiagnostics' => ['relatedInformation' => true],
                ],
            ],
            'workspaceFolders' => [[
                'uri' => $this->path_to_uri($this->root_path),
                'name' => basename($this->root_path),
            ]],
        ]);

        $capabilities = $initialize_response['result']['capabilities'] ?? [];
        $this->supports_pull_diagnostics = is_array($capabilities) && isset($capabilities['diagnosticProvider']);

        $this->send_message([
            'jsonrpc' => '2.0',
            'method' => 'initialized',
            'params' => new stdClass(),
        ]);

        if ($this->settings !== []) {
            $this->send_message([
                'jsonrpc' => '2.0',
                'method' => 'workspace/didChangeConfiguration',
                'params' => ['settings' => $this->configuration_settings()],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     * @throws RuntimeException
     */
    public function diagnostics(string $file_path): array
    {
        $source = file_get_contents($file_path);
        if (!is_string($source)) {
            throw new RuntimeException('Unable to read ' . $file_path . '.');
        }

        $uri = $this->path_to_uri($file_path);
        unset($this->published_diagnostics[$uri]);
        $this->send_message([
            'jsonrpc' => '2.0',
            'method' => 'textDocument/didOpen',
            'params' => [
                'textDocument' => [
                    'uri' => $uri,
                    'languageId' => $this->language_id,
                    'version' => 1,
                    'text' => $source,
                ],
            ],
        ]);

        if ($this->supports_pull_diagnostics) {
            $response = $this->request('textDocument/diagnostic', [
                'textDocument' => ['uri' => $uri],
            ]);
            $items = $response['result']['items'] ?? [];
            if (is_array($items)) {
                return array_values(array_filter($items, 'is_array'));
            }
        }

        $this->wait_for_published_diagnostics($uri);
        if (!array_key_exists($uri, $this->published_diagnostics)) {
            throw new RuntimeException($this->server_name . ' did not publish diagnostics for ' . $file_path . '. No clean result can be reported.');
        }

        return $this->published_diagnostics[$uri];
    }

    public function close(): void
    {
        if (!is_resource($this->process)) {
            return;
        }

        try {
            $this->request('shutdown', new stdClass());
            $this->send_message([
                'jsonrpc' => '2.0',
                'method' => 'exit',
                'params' => new stdClass(),
            ]);
        } catch (RuntimeException) {
            proc_terminate($this->process);
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_close($this->process);
        $this->process = null;
    }

    /**
     * @param array<string, mixed>|stdClass $params
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    private function request(string $method, array|stdClass $params): array
    {
        $request_id = $this->next_request_id++;
        $this->send_message([
            'jsonrpc' => '2.0',
            'id' => $request_id,
            'method' => $method,
            'params' => $params,
        ]);

        while (true) {
            $message = $this->read_message();
            if (($message['id'] ?? null) === $request_id) {
                if (isset($message['error'])) {
                    $error = $message['error'];
                    $detail = is_array($error) ? ($error['message'] ?? 'Unknown LSP error.') : 'Unknown LSP error.';
                    throw new RuntimeException((string) $detail);
                }

                return $message;
            }

            $this->handle_server_notification($message);

            if (isset($message['method'], $message['id'])) {
                $this->respond_to_server_request($message);
                continue;
            }
        }
    }

    /** @param array<string, mixed> $message */
    private function respond_to_server_request(array $message): void
    {
        $method = $message['method'] ?? '';
        $params = $message['params'] ?? [];
        $result = null;

        if ($method === 'workspace/configuration') {
            $result = [];
            $items = is_array($params) ? ($params['items'] ?? []) : [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $section = is_array($item) && is_string($item['section'] ?? null) ? $item['section'] : '';
                    $result[] = $section === $this->server_name ? $this->settings : null;
                }
            }
        } elseif ($method === 'workspace/workspaceFolders') {
            $result = [[
                'uri' => $this->path_to_uri($this->root_path),
                'name' => basename($this->root_path),
            ]];
        } elseif ($method === 'workspace/applyEdit') {
            $result = ['applied' => false, 'failureReason' => 'This diagnostics client does not apply edits.'];
        }

        $this->send_message(['jsonrpc' => '2.0', 'id' => $message['id'], 'result' => $result]);
    }

    /** @return array<string, mixed> */
    private function configuration_settings(): array
    {
        if (strtolower($this->server_name) === 'intelephense') {
            return ['intelephense' => $this->settings];
        }

        return $this->settings;
    }

    /** @throws RuntimeException */
    private function wait_for_published_diagnostics(string $uri): void
    {
        $deadline = microtime(true) + min($this->timeout, 5.0);
        while (!array_key_exists($uri, $this->published_diagnostics)) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                return;
            }
            try {
                $message = $this->read_message($remaining);
            } catch (RuntimeException $error) {
                if (str_contains($error->getMessage(), 'Timed out waiting')) {
                    return;
                }
                throw $error;
            }
            $this->handle_server_notification($message);
            if (isset($message['method'], $message['id'])) {
                $this->respond_to_server_request($message);
            }
        }
    }

    /** @param array<string, mixed> $message */
    private function handle_server_notification(array $message): void
    {
        if (($message['method'] ?? null) !== 'textDocument/publishDiagnostics') {
            return;
        }

        $params = $message['params'] ?? [];
        if (!is_array($params) || !is_string($params['uri'] ?? null)) {
            return;
        }
        $items = $params['diagnostics'] ?? [];
        $this->published_diagnostics[$params['uri']] = is_array($items)
            ? array_values(array_filter($items, 'is_array'))
            : [];
    }

    /** @param array<string, mixed> $message */
    /** @throws RuntimeException */
    private function send_message(array $message): void
    {
        try {
            $body = (string) json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $error) {
            throw new RuntimeException('Could not encode the LSP message.', 0, $error);
        }
        $frame = 'Content-Length: ' . strlen($body) . "\r\n\r\n" . $body;
        $written = 0;

        while ($written < strlen($frame)) {
            $status = proc_get_status($this->process);
            if (is_array($status) && ($status['running'] ?? false) === false) {
                throw new RuntimeException('PHP-LSP exited before it received the LSP request (exit code ' . (int) ($status['exitcode'] ?? -1) . '). Check the server output above.');
            }

            $write_error = '';
            set_error_handler(static function (int $severity, string $message) use (&$write_error): bool {
                $write_error = $message;
                return true;
            });
            try {
                $count = fwrite($this->pipes[0], substr($frame, $written));
            } finally {
                restore_error_handler();
            }
            if ($count === false || $count === 0) {
                $detail = $write_error !== '' ? ' ' . $write_error : '';
                throw new RuntimeException('Could not write to ' . $this->server_name . '.' . $detail);
            }
            $written += $count;
        }

        fflush($this->pipes[0]);
    }

    /** @return array<string, mixed> */
    private function read_message(?float $timeout = null): array
    {
        $deadline = microtime(true) + ($timeout ?? $this->timeout);

        while (true) {
            $header_end = strpos($this->buffer, "\r\n\r\n");
            if ($header_end !== false) {
                $header = substr($this->buffer, 0, $header_end);
                if (preg_match('/(?:^|\r\n)Content-Length:\s*(\d+)/i', $header, $matches) !== 1) {
                    throw new RuntimeException('PHP-LSP sent a message without Content-Length.');
                }

                $body_start = $header_end + 4;
                $body_length = (int) $matches[1];
                if (strlen($this->buffer) >= $body_start + $body_length) {
                    $body = substr($this->buffer, $body_start, $body_length);
                    $this->buffer = substr($this->buffer, $body_start + $body_length);
                    $message = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($message)) {
                        throw new RuntimeException('PHP-LSP sent a non-object JSON message.');
                    }

                    return $message;
                }
            }

            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                throw new RuntimeException('Timed out waiting for a PHP-LSP response.');
            }

            $read = [$this->pipes[1]];
            $write = null;
            $except = null;
            $seconds = (int) $remaining;
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);
            $ready = stream_select($read, $write, $except, $seconds, $microseconds);
            if ($ready === false) {
                throw new RuntimeException('Could not read from the PHP-LSP process.');
            }
            if ($ready === 0) {
                throw new RuntimeException('Timed out waiting for a PHP-LSP response.');
            }

            $chunk = fread($this->pipes[1], 8192);
            if (!is_string($chunk) || ($chunk === '' && feof($this->pipes[1]))) {
                throw new RuntimeException('PHP-LSP exited before returning a response.');
            }
            $this->buffer .= $chunk;
        }
    }

    private function path_to_uri(string $path): string
    {
        return 'file://' . str_replace('%2F', '/', rawurlencode($path));
    }
}

/** @return array{root: string, servers: list<string>, auto_install_server: bool, timeout: float, language: ?string, files: list<string>} */
function parse_arguments(array $arguments): array
{
    array_shift($arguments);
    $root = getcwd();
    $servers = [];
    $language = null;
    $auto_install_server = true;
    $timeout = 20.0;
    $files = [];

    while ($arguments !== []) {
        $argument = array_shift($arguments);
        if ($argument === '--version') {
            fwrite(STDOUT, "lsp-diagnostics 2026.10.16\n");
            exit(0);
        }
        if ($argument === '--help' || $argument === '-h') {
            fwrite(STDOUT, "Usage: lsp-diagnostics [--root DIR] [--language LANGUAGE] [--server SERVER ...] [--timeout SECONDS] [FILE_OR_DIR ...]\n");
            fwrite(STDOUT, "Runs the configured Zed language servers for each detected language and combines their diagnostics.\n");
            fwrite(STDOUT, "Directories are scanned recursively; with no paths, the workspace is scanned.\n");
            fwrite(STDOUT, "PHP extensionless scripts and common JavaScript/TypeScript files are supported.\n");
            fwrite(STDOUT, "Use --server repeatedly to select multiple servers; PHP_LSP_COMMAND remains a PHP server override.\n");
            fwrite(STDOUT, "Use --no-install-server to disable automatic Intelephense setup.\n");
            exit(0);
        }
        if ($argument === '--no-install-server') {
            $auto_install_server = false;
            continue;
        }
        if ($argument === '--server' && ($arguments === [] || str_starts_with($arguments[0], '--'))) {
            // Bare --server keeps automatic server discovery enabled.
            continue;
        }
        if (in_array($argument, ['--root', '--server', '--timeout'], true)) {
            if ($arguments === []) {
                throw new InvalidArgumentException('Missing value for ' . $argument . '.');
            }
            $value = array_shift($arguments);
            if ($argument === '--root') {
                $root = $value;
            } elseif ($argument === '--server') {
                $servers[] = $value;
            } else {
                $timeout = (float) $value;
            }
            continue;
        }
        if ($argument === '--language') {
            if ($arguments === []) {
                throw new InvalidArgumentException('Missing value for --language.');
            }
            $language = array_shift($arguments);
            continue;
        }
        if (str_starts_with($argument, '--')) {
            throw new InvalidArgumentException('Unknown option ' . $argument . '.');
        }
        $files[] = $argument;
    }

    if (!is_string($root)) {
        throw new RuntimeException('Unable to determine the current directory.');
    }

    return [
        'root' => $root,
        'servers' => $servers,
        'auto_install_server' => $auto_install_server,
        'timeout' => $timeout,
        'language' => $language,
        'files' => $files,
    ];
}

/** @throws RuntimeException */
function install_intelephense(): array
{
    $cache_dir = lsp_cache_directory();
    $node = find_path_executable('node');
    $npm = find_path_executable('npm');
    if ($node === null || $npm === null) {
        throw new RuntimeException('Intelephense requires Node.js and npm. Install Node.js, or set PHP_LSP_COMMAND to Zed\'s Intelephense command.');
    }

    $script_path = $cache_dir . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'intelephense' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'intelephense.js';
    if (!is_file($script_path)) {
        fwrite(STDERR, 'php-lsp-diagnostics: installing Intelephense 1.18.5 with npm...' . PHP_EOL);
        $pipes = [];
        $process = proc_open(
            [$npm, 'install', '--prefix', $cache_dir, '--no-save', '--no-package-lock', '--ignore-scripts', '--no-audit', '--no-fund', 'intelephense@1.18.5'],
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            $cache_dir,
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start npm to install Intelephense.');
        }
        $exit_code = proc_close($process);
        if (!is_int($exit_code) || $exit_code !== 0 || !is_file($script_path)) {
            throw new RuntimeException('npm could not install Intelephense. Check Node.js/npm access or set PHP_LSP_COMMAND.');
        }
    }

    fwrite(STDERR, 'php-lsp-diagnostics: using Intelephense 1.18.5.' . PHP_EOL);
    return [$node, $script_path, '--stdio'];
}

/** @throws RuntimeException */
/** @throws RuntimeException */
function install_php_lsp(): string
{
    $cache_dir = lsp_cache_directory();
    $platform = match (PHP_OS_FAMILY) {
        'Linux' => 'unknown-linux-musl',
        'Darwin' => 'apple-darwin',
        default => throw new RuntimeException('Automatic PHP-LSP download is unsupported on this operating system.'),
    };
    $architecture = match (strtolower(php_uname('m'))) {
        'x86_64', 'amd64' => 'x86_64',
        'aarch64', 'arm64' => 'aarch64',
        default => throw new RuntimeException('Automatic PHP-LSP download is unsupported for this CPU architecture.'),
    };
    $binary_path = $cache_dir . DIRECTORY_SEPARATOR . 'php-lsp-' . $architecture . '-' . $platform;
    if (!is_link($binary_path) && is_file($binary_path) && is_executable($binary_path)) {
        return $binary_path;
    }

    $archive_name = 'php-lsp-' . $architecture . '-' . $platform . '.tar.gz';
    fwrite(STDERR, 'php-lsp-diagnostics: PHP-LSP is missing; downloading the matching official release...' . PHP_EOL);
    $context = stream_context_create([
        'http' => [
            'timeout' => 90,
            'follow_location' => 1,
            'header' => "User-Agent: php-lsp-diagnostics\r\nAccept: application/vnd.github+json\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $release_json = file_get_contents('https://api.github.com/repos/jorgsowa/php-lsp/releases/latest', false, $context);
    if (!is_string($release_json) || $release_json === '') {
        throw new RuntimeException('Could not fetch PHP-LSP release information. Check network access.');
    }
    $release = json_decode($release_json, true);
    if (!is_array($release) || !is_array($release['assets'] ?? null)) {
        throw new RuntimeException('The PHP-LSP release response was invalid.');
    }
    $assets = [];
    foreach ($release['assets'] as $asset) {
        if (is_array($asset) && is_string($asset['name'] ?? null) && is_string($asset['browser_download_url'] ?? null)) {
            $assets[$asset['name']] = $asset['browser_download_url'];
        }
    }
    $checksum_name = $archive_name . '.sha256';
    if (!isset($assets[$archive_name], $assets[$checksum_name])) {
        throw new RuntimeException('The PHP-LSP release has no binary for ' . $architecture . '-' . $platform . '.');
    }
    $archive = file_get_contents($assets[$archive_name], false, $context);
    $checksum_data = file_get_contents($assets[$checksum_name], false, $context);
    if (!is_string($archive) || $archive === '' || !is_string($checksum_data)
        || preg_match('/\b([a-f0-9]{64})\b/i', $checksum_data, $matches) !== 1
        || !hash_equals(strtolower($matches[1]), hash('sha256', $archive))) {
        throw new RuntimeException('The PHP-LSP download failed checksum validation.');
    }
    $tar_data = gzdecode($archive);
    if (!is_string($tar_data)) {
        throw new RuntimeException('The PHP-LSP release archive could not be decompressed.');
    }
    $binary = extract_tar_member($tar_data, 'php-lsp');
    $temporary_path = $binary_path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    if (file_put_contents($temporary_path, $binary, LOCK_EX) !== strlen($binary)) {
        if (is_file($temporary_path)) {
            unlink($temporary_path);
        }
        throw new RuntimeException('Could not write the PHP-LSP executable to the local cache.');
    }
    if (!chmod($temporary_path, 0700) || !rename($temporary_path, $binary_path)) {
        if (is_file($temporary_path)) {
            unlink($temporary_path);
        }
        throw new RuntimeException('Could not activate the cached PHP-LSP executable.');
    }
    fwrite(STDERR, 'php-lsp-diagnostics: PHP-LSP is ready in the local cache.' . PHP_EOL);
    return $binary_path;
}

/** @throws RuntimeException */
function extract_tar_member(string $tar_data, string $member_name): string
{
    $offset = 0;
    $length = strlen($tar_data);
    while ($offset + 512 <= $length) {
        $header = substr($tar_data, $offset, 512);
        if ($header === str_repeat("\0", 512)) {
            break;
        }
        $name = rtrim(substr($header, 0, 100), "\0");
        $prefix = rtrim(substr($header, 345, 155), "\0");
        $entry_name = $prefix === '' ? $name : $prefix . '/' . $name;
        $size_field = trim(substr($header, 124, 12), "\0 ");
        if ($size_field !== '' && preg_match('/^[0-7]+$/', $size_field) !== 1) {
            throw new RuntimeException('The PHP-LSP archive contains an invalid file size.');
        }
        $entry_size = $size_field === '' ? 0 : (int) octdec($size_field);
        $data_offset = $offset + 512;
        if ($entry_name === $member_name) {
            if ($entry_size < 1 || $data_offset + $entry_size > $length) {
                throw new RuntimeException('The PHP-LSP executable in the release archive is invalid.');
            }
            return substr($tar_data, $data_offset, $entry_size);
        }
        $offset = $data_offset + (int) (ceil($entry_size / 512) * 512);
    }
    throw new RuntimeException('The PHP-LSP executable was not found in the release archive.');
}

function lsp_cache_directory(): string
{
    $home = getenv('HOME');
    if (is_string($home) && $home !== '' && is_dir($home) && is_writable($home)) {
        $cache_dir = rtrim($home, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.cache' . DIRECTORY_SEPARATOR . 'webkernel' . DIRECTORY_SEPARATOR . 'php-lsp';
    } else {
        $effective_user = function_exists('posix_geteuid') ? (string) posix_geteuid() : hash('sha256', get_current_user());
        $cache_dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'webkernel-php-lsp-' . $effective_user;
    }

    if (!is_dir($cache_dir) && !mkdir($cache_dir, 0700, true) && !is_dir($cache_dir)) {
        throw new RuntimeException('Could not create a private PHP-LSP cache directory. Pass --server /path/to/php-lsp.');
    }
    if (is_link($cache_dir) || !is_writable($cache_dir)) {
        throw new RuntimeException('The PHP-LSP cache directory is not writable. Pass --server /path/to/php-lsp.');
    }
    chmod($cache_dir, 0700);

    return $cache_dir;
}

/** @return string|null */
function find_path_executable(string $executable): ?string
{
    $path = getenv('PATH');
    if (!is_string($path)) {
        return null;
    }

    foreach (explode(PATH_SEPARATOR, $path) as $directory) {
        $candidate = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $executable;
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/** @return array<string, mixed> */
function load_zed_settings(string $root): array
{
    $settings_paths = [];
    $explicit_settings = getenv('ZED_SETTINGS_PATH');
    if (is_string($explicit_settings) && $explicit_settings !== '') {
        $settings_paths[] = $explicit_settings;
    } else {
        $config_home = getenv('XDG_CONFIG_HOME');
        $home = getenv('HOME');
        if (is_string($config_home) && $config_home !== '') {
            $settings_paths[] = rtrim($config_home, DIRECTORY_SEPARATOR) . '/zed/settings.json';
        } elseif (is_string($home) && $home !== '') {
            $settings_paths[] = rtrim($home, DIRECTORY_SEPARATOR) . '/.config/zed/settings.json';
        }
    }
    $git_root = find_git_root($root);
    $directory = realpath($root);
    $workspace_boundary = $git_root ?? $directory;
    if (is_string($directory)) {
        while (is_string($directory)) {
            $workspace_settings = $directory . DIRECTORY_SEPARATOR . '.zed' . DIRECTORY_SEPARATOR . 'settings.json';
            if (is_file($workspace_settings)) {
                $settings_paths[] = $workspace_settings;
                break;
            }
            if ($directory === $workspace_boundary || dirname($directory) === $directory) {
                break;
            }
            $directory = dirname($directory);
        }
    }

    $settings = [];
    foreach (array_unique($settings_paths) as $settings_path) {
        if (!is_file($settings_path)) {
            continue;
        }
        $contents = file_get_contents($settings_path);
        if (!is_string($contents)) {
            continue;
        }
        $decoded = decode_jsonc($contents);
        $settings = array_replace_recursive($settings, $decoded);
    }

    return $settings;
}

function find_git_root(string $root): ?string
{
    $pipes = [];
    $process = proc_open(
        ['git', 'rev-parse', '--show-toplevel'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'a']],
        $pipes,
        $root,
        null,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        return null;
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $exit_code = proc_close($process);
    if (!is_string($output) || $exit_code !== 0) {
        return null;
    }

    $git_root = realpath(trim($output));
    return is_string($git_root) ? $git_root : null;
}

/** @return list<string> */
function configured_server_ids(string $zed_language, array $settings): array
{
    $configured = $settings['languages'][$zed_language]['language_servers'] ?? null;
    $candidates = match ($zed_language) {
        'PHP' => ['phpactor', 'intelephense', 'php-lsp', 'phptools', 'phpantom'],
        'JavaScript', 'TypeScript' => ['typescript-language-server', 'vtsls', 'deno', 'eslint', 'biome', 'oxc'],
        'Python' => ['basedpyright', 'pyright', 'pylsp'],
        'Rust' => ['rust-analyzer'],
        'Go' => ['gopls'],
        'JSON', 'JSONC' => ['vscode-json-language-server'],
        'HTML' => ['vscode-html-language-server'],
        'CSS' => ['vscode-css-language-server'],
        default => [],
    };

    if (!is_array($configured)) {
        $configured = ['...'];
    }

    $selected = [];
    $excluded = [];
    $include_available = false;
    foreach ($configured as $server_id) {
        if (!is_string($server_id)) {
            continue;
        }
        if ($server_id === '...') {
            $include_available = true;
        } elseif (str_starts_with($server_id, '!')) {
            $excluded[] = substr($server_id, 1);
        } else {
            $selected[] = $server_id;
        }
    }

    if ($include_available) {
        foreach ($candidates as $candidate) {
            if (!in_array($candidate, $excluded, true) && server_command_is_available($candidate, $settings)) {
                $selected[] = $candidate;
            }
        }
    }

    $selected = array_values(array_unique(array_filter($selected, static fn (string $server_id): bool => !in_array($server_id, $excluded, true))));
    return $selected;
}

/** @return list<string> */
function discover_server_command(string $server_id, array $settings, bool $php_override = false, bool $allow_install = true): array
{
    if (str_contains($server_id, DIRECTORY_SEPARATOR)) {
        return resolve_server_executable($server_id);
    }

    if ($php_override && $server_id === 'intelephense') {
        $environment_command = getenv('PHP_LSP_COMMAND');
        if (is_string($environment_command) && $environment_command !== '') {
            $parts = str_getcsv(trim($environment_command), ' ', '"', '\\');
            if (is_array($parts) && $parts !== []) {
                $parts = array_values(array_filter($parts, 'is_string'));
                if ($parts !== []) {
                    $parts[0] = resolve_server_executable($parts[0])[0];
                    return $parts;
                }
            }
        }
    }

    $binary = $settings['lsp'][$server_id]['binary'] ?? [];
    if (is_array($binary) && is_string($binary['path'] ?? null)) {
        $command = resolve_server_executable($binary['path']);
        $arguments = $binary['arguments'] ?? [];
        return array_merge($command, is_array($arguments) ? array_values(array_filter($arguments, 'is_string')) : []);
    }

    $running_command = find_running_server_command($server_id);
    if ($running_command !== null) {
        return $running_command;
    }

    $path_command = command_from_path($server_id);
    if ($path_command !== null) {
        return $path_command;
    }

    if ($server_id === 'intelephense') {
        if (!$allow_install) {
            throw new RuntimeException('Intelephense is not installed.');
        }
        return install_intelephense();
    }

    if ($server_id === 'php-lsp') {
        if (!$allow_install) {
            throw new RuntimeException('PHP-LSP is not installed.');
        }
        return [install_php_lsp()];
    }

    throw new RuntimeException('Could not locate the configured Zed language server "' . $server_id . '". Open a file for this language in Zed or pass --server with its executable.');
}

function server_command_is_available(string $server_id, array $settings): bool
{
    try {
        discover_server_command($server_id, $settings, false, false);
        return true;
    } catch (RuntimeException) {
        return false;
    }
}

/** @return list<string>|null */
function find_running_server_command(string $server_id): ?array
{
    if (!is_dir('/proc')) {
        return null;
    }

    $process_ids = scandir('/proc');
    if (!is_array($process_ids)) {
        return null;
    }
    rsort($process_ids, SORT_NUMERIC);

    foreach ($process_ids as $process_id) {
        if (!ctype_digit($process_id) || (int) $process_id === getmypid()) {
            continue;
        }
        $raw_command = file_get_contents('/proc/' . $process_id . '/cmdline');
        if (!is_string($raw_command) || $raw_command === '') {
            continue;
        }
        $command = array_values(array_filter(explode("\0", $raw_command), 'strlen'));
        $first_argument = strtolower(basename($command[0] ?? ''));
        if (in_array($first_argument, ['bash', 'sh', 'zsh', 'fish'], true)) {
            continue;
        }
        $command_line = strtolower(implode(' ', $command));
        $needle = match ($server_id) {
            'phpactor' => 'phpactor.phar',
            'intelephense' => 'intelephense.js',
            'php-lsp' => 'php-lsp-v',
            default => strtolower(str_replace('_', '-', $server_id)),
        };
        if (str_contains($command_line, $needle)) {
            return $command;
        }
    }

    return null;
}

function command_from_path(string $server_id): ?array
{
    $executable_names = match ($server_id) {
        'php-lsp' => ['php-lsp'],
        'intelephense' => ['intelephense'],
        'phpactor' => ['phpactor'],
        'typescript-language-server' => ['typescript-language-server'],
        'vtsls' => ['vtsls'],
        'deno' => ['deno'],
        'eslint' => ['vscode-eslint-language-server'],
        'biome' => ['biome'],
        'oxc' => ['oxc_language_server'],
        'basedpyright' => ['basedpyright-langserver'],
        'pyright' => ['pyright-langserver'],
        'pylsp' => ['pylsp'],
        'rust-analyzer' => ['rust-analyzer'],
        'gopls' => ['gopls'],
        'vscode-json-language-server' => ['vscode-json-language-server'],
        'vscode-html-language-server' => ['vscode-html-language-server'],
        'vscode-css-language-server' => ['vscode-css-language-server'],
        default => [$server_id],
    };

    foreach ($executable_names as $executable_name) {
        $executable = find_path_executable($executable_name);
        if ($executable === null) {
            continue;
        }
        $arguments = match ($server_id) {
            'phpactor' => ['language-server'],
            'intelephense', 'typescript-language-server', 'vtsls', 'eslint', 'vscode-json-language-server', 'vscode-html-language-server', 'vscode-css-language-server', 'basedpyright', 'pyright' => ['--stdio'],
            default => [],
        };
        return array_merge([$executable], $arguments);
    }

    return null;
}

/** @return array<string, mixed> */
function decode_jsonc(string $contents): array
{
    $without_comments = '';
    $length = strlen($contents);
    $in_string = false;
    $escaped = false;
    for ($index = 0; $index < $length; $index++) {
        $character = $contents[$index];
        $next = $contents[$index + 1] ?? '';
        if ($in_string) {
            $without_comments .= $character;
            if ($escaped) {
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === '"') {
                $in_string = false;
            }
            continue;
        }
        if ($character === '"') {
            $in_string = true;
            $without_comments .= $character;
        } elseif ($character === '/' && $next === '/') {
            while ($index < $length && $contents[$index] !== "\n") {
                $index++;
            }
            $without_comments .= "\n";
        } elseif ($character === '/' && $next === '*') {
            $index += 2;
            while ($index + 1 < $length && !($contents[$index] === '*' && $contents[$index + 1] === '/')) {
                $index++;
            }
            $index++;
        } else {
            $without_comments .= $character;
        }
    }

    $json = '';
    $in_string = false;
    $escaped = false;
    $length = strlen($without_comments);
    for ($index = 0; $index < $length; $index++) {
        $character = $without_comments[$index];
        if ($in_string) {
            $json .= $character;
            if ($escaped) {
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === '"') {
                $in_string = false;
            }
            continue;
        }
        if ($character === '"') {
            $in_string = true;
            $json .= $character;
        } elseif ($character === ',') {
            $next_index = $index + 1;
            while ($next_index < $length && ctype_space($without_comments[$next_index])) {
                $next_index++;
            }
            if (!in_array($without_comments[$next_index] ?? '', ['}', ']'], true)) {
                $json .= $character;
            }
        } else {
            $json .= $character;
        }
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function resolve_server_executable(string $executable): array
{
    if (str_contains($executable, DIRECTORY_SEPARATOR)) {
        $resolved = realpath($executable);
        if (is_string($resolved) && is_file($resolved) && is_executable($resolved)) {
            return [$resolved];
        }
        throw new RuntimeException('Language-server executable is not runnable: ' . $executable);
    }

    $path = getenv('PATH');
    if (is_string($path)) {
        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $executable;
            if (is_file($candidate) && is_executable($candidate)) {
                return [$candidate];
            }
        }
    }

    throw new RuntimeException('Language-server executable is not runnable: ' . $executable);
}

/** @return list<string> */
function select_source_files(string $root, array $requested_files, ?string $language = null): array
{
    $files = [];
    if ($requested_files === []) {
        $pipes = [];
        $process = proc_open(
            ['git', 'ls-files', '-z'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'a']],
            $pipes,
            $root,
            null,
            ['bypass_shell' => true],
        );
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($process);
            if (is_string($output)) {
                $ignored = ['.git', '_old', 'dependencies', 'node_modules', 'runtime', 'vendor'];
                foreach (array_filter(explode("\0", $output), 'strlen') as $relative_path) {
                    $segments = explode(DIRECTORY_SEPARATOR, $relative_path);
                    if (array_intersect($segments, $ignored) !== []) {
                        continue;
                    }

                    $file_path = $root . DIRECTORY_SEPARATOR . $relative_path;
                    if (source_matches_language($file_path, $language)) {
                        $files[] = $file_path;
                    }
                }
            }
        }

        if ($files === []) {
            $files = source_files_in_directory($root, $language);
        }
    } else {
        foreach ($requested_files as $file) {
            $path = str_starts_with($file, DIRECTORY_SEPARATOR)
                ? $file
                : $root . DIRECTORY_SEPARATOR . $file;
            $resolved_path = realpath($path);
            if (is_string($resolved_path) && is_dir($resolved_path)) {
                $files = array_merge($files, source_files_in_directory($resolved_path, $language));
            } else {
                $files[] = $path;
            }
        }
    }

    $selected = [];
    $seen = [];
    foreach ($files as $file) {
        $absolute_path = realpath($file);
        if (!is_string($absolute_path) || !is_file($absolute_path)) {
            throw new RuntimeException('Source file or directory does not exist: ' . $file);
        }
        if (!str_starts_with($absolute_path, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('File is outside the workspace: ' . $absolute_path);
        }
        if (!isset($seen[$absolute_path])) {
            $selected[] = $absolute_path;
            $seen[$absolute_path] = true;
        }
    }

    if ($selected === []) {
        throw new RuntimeException('No supported source files found in the requested path.');
    }

    return $selected;
}

/** @return list<string> */
function source_files_in_directory(string $directory, ?string $language = null): array
{
    $ignored_directories = ['.git', '_old', 'dependencies', 'node_modules', 'runtime', 'vendor'];
    $directory_iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
    $filtered_iterator = new RecursiveCallbackFilterIterator(
        $directory_iterator,
        static function (SplFileInfo $entry) use ($ignored_directories): bool {
            return !$entry->isDir() || !in_array($entry->getFilename(), $ignored_directories, true);
        },
    );
    $iterator = new RecursiveIteratorIterator($filtered_iterator);
    $files = [];

    foreach ($iterator as $entry) {
        if ($entry->isFile() && source_matches_language($entry->getPathname(), $language)) {
            $files[] = $entry->getPathname();
        }
    }

    return $files;
}

function source_matches_language(string $file_path, ?string $language): bool
{
    $detected_profile = language_profile($file_path);
    if ($detected_profile === null) {
        return false;
    }

    return $language === null || language_profile($file_path, $language) === $detected_profile;
}

/** @return array{zed_name: string, language_id: string}|null */
function language_profile(string $file_path, ?string $override = null): ?array
{
    $profiles = [
        'php' => ['zed_name' => 'PHP', 'language_id' => 'php'],
        'js' => ['zed_name' => 'JavaScript', 'language_id' => 'javascript'],
        'mjs' => ['zed_name' => 'JavaScript', 'language_id' => 'javascript'],
        'cjs' => ['zed_name' => 'JavaScript', 'language_id' => 'javascript'],
        'jsx' => ['zed_name' => 'JavaScript', 'language_id' => 'javascriptreact'],
        'ts' => ['zed_name' => 'TypeScript', 'language_id' => 'typescript'],
        'mts' => ['zed_name' => 'TypeScript', 'language_id' => 'typescript'],
        'cts' => ['zed_name' => 'TypeScript', 'language_id' => 'typescript'],
        'tsx' => ['zed_name' => 'TypeScript', 'language_id' => 'typescriptreact'],
        'json' => ['zed_name' => 'JSON', 'language_id' => 'json'],
        'jsonc' => ['zed_name' => 'JSONC', 'language_id' => 'jsonc'],
        'py' => ['zed_name' => 'Python', 'language_id' => 'python'],
        'rs' => ['zed_name' => 'Rust', 'language_id' => 'rust'],
        'go' => ['zed_name' => 'Go', 'language_id' => 'go'],
        'rb' => ['zed_name' => 'Ruby', 'language_id' => 'ruby'],
        'java' => ['zed_name' => 'Java', 'language_id' => 'java'],
        'c' => ['zed_name' => 'C', 'language_id' => 'c'],
        'h' => ['zed_name' => 'C', 'language_id' => 'c'],
        'cpp' => ['zed_name' => 'C++', 'language_id' => 'cpp'],
        'cc' => ['zed_name' => 'C++', 'language_id' => 'cpp'],
        'css' => ['zed_name' => 'CSS', 'language_id' => 'css'],
        'html' => ['zed_name' => 'HTML', 'language_id' => 'html'],
        'vue' => ['zed_name' => 'Vue', 'language_id' => 'vue'],
        'svelte' => ['zed_name' => 'Svelte', 'language_id' => 'svelte'],
        'yaml' => ['zed_name' => 'YAML', 'language_id' => 'yaml'],
        'yml' => ['zed_name' => 'YAML', 'language_id' => 'yaml'],
        'sh' => ['zed_name' => 'Shell Script', 'language_id' => 'shellscript'],
    ];

    if ($override !== null) {
        foreach ($profiles as $profile) {
            if (strcasecmp($profile['zed_name'], $override) === 0 || strcasecmp($profile['language_id'], $override) === 0) {
                return $profile;
            }
        }
        return ['zed_name' => $override, 'language_id' => strtolower($override)];
    }

    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if (isset($profiles[$extension])) {
        return $profiles[$extension];
    }
    if ($extension !== '' || !is_file($file_path)) {
        return null;
    }

    $handle = fopen($file_path, 'rb');
    if (!is_resource($handle)) {
        return null;
    }

    try {
        $prefix = fread($handle, 512);
    } finally {
        fclose($handle);
    }

    if (!is_string($prefix)) {
        return null;
    }

    if (preg_match('/\A(?:\xEF\xBB\xBF)?(?:#![^\r\n]*\r?\n)?\s*<\?php\b/i', $prefix) === 1) {
        return $profiles['php'];
    }
    if (preg_match('/\A#![^\r\n]*(?:node|deno|bun)\b/i', $prefix) === 1) {
        return $profiles['js'];
    }
    if (preg_match('/\A#![^\r\n]*python\b/i', $prefix) === 1) {
        return $profiles['py'];
    }
    if (preg_match('/\A#![^\r\n]*(?:ba)?sh\b/i', $prefix) === 1) {
        return $profiles['sh'];
    }

    return null;
}

try {
    $arguments = parse_arguments($argv);
    $root = realpath($arguments['root']);
    if (!is_string($root) || !is_dir($root)) {
        throw new RuntimeException('Workspace root is not a directory: ' . $arguments['root'] . '. Pass file paths after --root DIR.');
    }

    $files = select_source_files($root, $arguments['files'], $arguments['language']);
    $settings = load_zed_settings($root);
    $file_profiles = [];
    foreach ($files as $file) {
        $profile = language_profile($file, $arguments['language']);
        if ($profile === null) {
            throw new RuntimeException('Cannot determine the language for ' . $file . '. Pass --language.');
        }
        $file_profiles[$file] = $profile;
    }

    $groups = [];
    $incomplete = [];
    foreach ($file_profiles as $file => $profile) {
        $server_ids = $arguments['servers'];
        if ($server_ids === []) {
            $environment_command = getenv('PHP_LSP_COMMAND');
            $server_ids = $profile['zed_name'] === 'PHP' && is_string($environment_command) && $environment_command !== ''
                ? ['intelephense']
                : configured_server_ids($profile['zed_name'], $settings);
        }
        if ($server_ids === []) {
            if ($profile['zed_name'] === 'PHP' && $arguments['auto_install_server']) {
                $server_ids = ['php-lsp'];
            } else {
                $incomplete[] = 'No language servers are configured or running for ' . $profile['zed_name'] . ' (' . basename($file) . ').';
                continue;
            }
        }
        foreach ($server_ids as $server_id) {
            $group_key = $server_id . "\0" . $profile['language_id'];
            $groups[$group_key] ??= [
                'server_id' => $server_id,
                'language_id' => $profile['language_id'],
                'files' => [],
            ];
            $groups[$group_key]['files'][] = $file;
        }
    }

    $diagnostics = [];
    $server_file_counts = [];

    foreach ($groups as $group) {
        $server_id = $group['server_id'];
        $server_settings = $settings['lsp'][$server_id]['settings'] ?? [];
        if (!is_array($server_settings)) {
            $server_settings = [];
        }
        $php_override = $group['language_id'] === 'php' && getenv('PHP_LSP_COMMAND') !== false;
        $server_name = $server_id === 'intelephense' ? 'Intelephense' : $server_id;
        $client = null;
        try {
            if (!$arguments['auto_install_server'] && $server_id === 'intelephense' && !server_command_is_available($server_id, $settings)) {
                throw new RuntimeException('Intelephense is not installed and automatic installation is disabled.');
            }
            $command = discover_server_command($server_id, $settings, $php_override, $arguments['auto_install_server']);
            $client = new LspDiagnostics(
                $command,
                $server_name,
                $group['language_id'],
                $root,
                $arguments['timeout'],
                $server_settings,
            );
            $client->start();
            foreach ($group['files'] as $file) {
                $server_file_counts[$server_name] = ($server_file_counts[$server_name] ?? 0) + 1;
                foreach ($client->diagnostics($file) as $diagnostic) {
                    $diagnostics[] = [$file, $server_name, $diagnostic];
                }
            }
        } catch (Throwable $error) {
            $incomplete[] = $server_name . ': ' . $error->getMessage();
        } finally {
            $client?->close();
        }
    }

    foreach ($diagnostics as [$file, $server_name, $diagnostic]) {
        $range = $diagnostic['range']['start'] ?? [];
        $line = (int) ($range['line'] ?? 0) + 1;
        $column = (int) ($range['character'] ?? 0) + 1;
        $severity = [1 => 'Error', 2 => 'Warning', 3 => 'Information', 4 => 'Hint'][$diagnostic['severity'] ?? 0] ?? 'Unknown';
        $code = $diagnostic['code'] ?? '';
        printf("[%s] %s:%d:%d: %s %s: %s\n", $server_name, substr($file, strlen($root) + 1), $line, $column, $severity, (string) $code, (string) ($diagnostic['message'] ?? ''));
    }

    if ($diagnostics === []) {
        foreach ($server_file_counts as $server_name => $file_count) {
            printf("%s published no diagnostics for %d file(s).\n", $server_name, $file_count);
        }
    }

    foreach ($incomplete as $message) {
        fwrite(STDERR, 'php-lsp-diagnostics: incomplete: ' . $message . PHP_EOL);
    }

    exit($incomplete !== [] ? 2 : ($diagnostics === [] ? 0 : 1));
} catch (Throwable $error) {
    fwrite(STDERR, 'php-lsp-diagnostics: ' . $error->getMessage() . PHP_EOL);
    exit(2);
}
