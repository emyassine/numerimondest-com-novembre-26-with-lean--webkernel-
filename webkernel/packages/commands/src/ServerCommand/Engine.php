<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\ServerCommand;

use Webkernel\Console\ExitCode;
use Webkernel\Console\Terminal;

require_once __DIR__.'/MountPaths.php';

/**
 * PHP built-in server process for {@see \Webkernel\Commands\ServerCommand}.
 *
 * @phpstan-type IncludeRow array{ms: float|null, run_ms: float|null, read_ms: float|null, path: string}
 * @phpstan-type RequestTimings array{
 *   render_ms?: float|null,
 *   times?: list<IncludeRow>,
 *   classes?: list<string>,
 *   mem?: int|string|null,
 *   type?: string
 * }
 */
final class Engine
{
    private const int MAX_PORT_TRIES = 10;
    private const int METRIC_INTEGER_COLUMNS = 3;
    private const int METRIC_PRECISION = 2;

    private string $host = '127.0.0.1';
    private int $port = 8000;
    private string $public_dir = '';
    private string $root = '';
    private string $router = '';
    /** @var array<string, string> URL prefix => document root */
    private array $mounts = [];
    private bool $profile_lifecycle = false;
    /** true force on, false force off, null inherit php.ini / preference */
    private ?bool $jit = null;
    /** @var resource|null */
    private $process = null;
    /** @var array<int, resource> */
    private array $pipes = [];
    private string $output_buffer = '';
    private bool $banner_shown = false;
    private bool $address_in_use = false;
    private bool $is_first_request = true;
    private bool $interrupted = false;
    private int $interrupt_exit_code = 130;
    private int $server_started_at = 0;
    /** @var array<string, array{count: int, sum: float, min: float|null, max: float|null}> */
    private array $summary_metrics = [
        'render' => ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null],
        'request' => ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null],
        'memory' => ['count' => 0, 'sum' => 0.0, 'min' => null, 'max' => null],
    ];
    /** @var array<int, int> */
    private array $status_counts = [];

    /** @var array<int, array{started: int|float, request: string, status: int, render_ms: float|null, request_ms: float|null, type: string, times: list<array{ms: float|null, run_ms: float|null, read_ms: float|null, path: string}>, classes: list<string>, mem: int|null}> */
    private array $requests_pool = [];

    /**
     * @param string    $host
     * @param int       $port
     * @param bool      $profile_lifecycle
     * @param bool|null $jit
     *
     * @return ExitCode
     */
    public function serve(string $host = '127.0.0.1', int $port = 8000, bool $profile_lifecycle = false, ?bool $jit = null, array $mounts = []): ExitCode
    {
        if (! self::valid_host($host) || $port < 1 || $port > 65535) {
            fwrite(STDERR, "Invalid server host or port.\n");

            return ExitCode::ERROR;
        }
        $this->host = $host;
        $this->port = $port;
        $this->profile_lifecycle = $profile_lifecycle;
        $this->jit = $jit;
        $normalized_mounts = [];
        foreach ($mounts as $mount) {
            if (!is_string($mount) || !preg_match('#^(/[^=:]*?)[=:](.+)$#', $mount, $parts)) {
                fwrite(STDERR, "Invalid mount. Use --mount=/prefix:/path/to/app\n");
                return ExitCode::ERROR;
            }
            $prefix = rtrim($parts[1], '/');
            $directory = realpath($parts[2]);
            if (!preg_match('#^/(?:[a-zA-Z0-9._~-]+/)*[a-zA-Z0-9._~-]+$#D', $prefix) || preg_match('#(?:^|/)\.\.?(/|$)#', $prefix) || !is_string($directory) || !is_dir($directory) || !is_file($directory.'/index.php')) {
                fwrite(STDERR, "Mounts need a URL prefix and an application directory containing index.php.\n");
                return ExitCode::ERROR;
            }
            if (isset($normalized_mounts[$prefix])) {
                fwrite(STDERR, "Duplicate mount prefix: {$prefix}\n");
                return ExitCode::ERROR;
            }
            $normalized_mounts[$prefix] = $directory;
        }
        $this->server_started_at = \hrtime(true);
        $this->public_dir = base_path('public');
        $this->root = base_path();
        $this->router = __DIR__.'/router.php';
        uksort($normalized_mounts, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $this->mounts = $normalized_mounts;
        $this->register_signals();
        for ($try = 0; $try < self::MAX_PORT_TRIES; $try++) {
            if ($this->interrupted) {
                $this->print_summary();

                return ExitCode::tryFrom($this->interrupt_exit_code) ?? ExitCode::INTERRUPTED;
            }
            $exit_code = $this->run_process();
            if ($this->interrupted) {
                $this->print_summary();

                return ExitCode::tryFrom($this->interrupt_exit_code) ?? ExitCode::INTERRUPTED;
            }
            if ($this->address_in_use) {
                $this->address_in_use = false;
                $this->banner_shown = false;
                $this->port++;
                continue;
            }

            return ExitCode::tryFrom(\is_int($exit_code) ? $exit_code : 0) ?? ExitCode::SUCCESS;
        }
        \fwrite(STDERR, "Unable to listen on {$this->host} after ".self::MAX_PORT_TRIES." port attempts.\n");

        return ExitCode::ERROR;
    }

    /**
     * @return int|null Process exit code, or null when the listen loop is still running
     */
    private function run_process(): ?int
    {
        $command = [PHP_BINARY, '-d', 'log_errors=1', '-d', 'error_log='];
        if ($this->jit === true) {
            foreach (Performance::jit_engine_args() as $part) {
                $command[] = $part;
            }
        } elseif ($this->jit === false) {
            foreach (Performance::jit_disable_args() as $part) {
                $command[] = $part;
            }
        }
        $command[] = '-S';
        $listen_host = \str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;
        $command[] = "{$listen_host}:{$this->port}";
        $command[] = '-t';
        $command[] = $this->public_dir;
        $command[] = $this->router;
        $env = \getenv();
        if (! \is_array($env)) {
            $env = [];
        }
        $env['WEBKERNEL_SERVER_MOUNTS'] = json_encode($this->mounts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if ($this->profile_lifecycle) {
            $env['WEBKERNEL_PROFILE_LIFECYCLE'] = '1';
        }
        $pipes = [];
        $process = \proc_open($command, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['redirect', 1],
        ], $pipes, $this->root, $env, ['bypass_shell' => true]);
        if ($process === false) {
            \fwrite(STDERR, "Failed to start PHP development server.\n");

            return 1;
        }
        $this->process = $process;
        $stdout = $pipes[1] ?? null;
        if ($stdout === null) {
            $this->close_process();
            \fwrite(STDERR, "Failed to capture server output.\n");

            return 1;
        }
        /** @var resource $stdout */
        $kept = [];
        foreach ($pipes as $fd => $pipe) {
            if ($pipe === null) {
                continue;
            }
            /** @var resource $pipe */
            $kept[(int) $fd] = $pipe;
        }
        $this->pipes = $kept;
        \stream_set_blocking($stdout, false);
        while (true) {
            $chunk = \fread($stdout, 8192);
            if (\is_string($chunk) && $chunk !== '') {
                $this->ingest($chunk);
            }
            $status = \proc_get_status($process);
            if (! $status['running']) {
                $leftover = \stream_get_contents($stdout);
                if (\is_string($leftover) && $leftover !== '') {
                    $this->ingest($leftover);
                }
                $this->close_process();

                return $status['exitcode'];
            }
            \usleep(50_000);
        }
    }

    /**
     * @param string $chunk Raw stdout from the PHP built-in server
     *
     * @return void
     */
    private function ingest(string $chunk): void
    {
        $this->output_buffer .= $chunk;
        $lines = \explode("\n", $this->output_buffer);
        $this->output_buffer = (string) \array_pop($lines);
        foreach ($lines as $line) {
            $this->handle_line(\trim($line));
        }
    }

    /**
     * @param string $line One stderr/stdout line from the child server
     *
     * @return void
     */
    private function handle_line(string $line): void
    {
        if ($line === '') {
            return;
        }
        if (\str_contains($line, 'Failed to listen') || \str_contains($line, 'Address already in use')) {
            $this->address_in_use = true;

            return;
        }
        if (\str_contains($line, 'Development Server (http')) {
            if (! $this->banner_shown) {
                $this->banner_shown = true;
                $this->render_banner();
            }

            return;
        }
        if (\str_contains($line, 'Closed without sending a request') || \str_contains($line, 'Failed to poll event')) {
            return;
        }

        $port = $this->request_port($line);
        if (\str_ends_with($line, ' Accepted') && $port !== null) {
            $this->requests_pool[$port] = [
                'started' => \hrtime(true), 'request' => '', 'status' => 200, 'render_ms' => null, 'request_ms' => null, 'type' => 'WEB',
                'times' => [], 'classes' => [], 'mem' => null,
            ];

            return;
        }

        if (\str_starts_with($line, 'webkernel-profile-json ')) {
            return;
        }

        if (\preg_match('/^webkernel-include (\S+) (\S+) (\S+) (.+)$/', $line, $tm) === 1) {
            $this->trace_time([
                'ms' => $tm[1] === '?' ? null : (float) $tm[1],
                'run_ms' => $tm[2] === '?' ? null : (float) $tm[2],
                'read_ms' => $tm[3] === '?' ? null : (float) $tm[3],
                'path' => $tm[4],
            ]);

            return;
        }
        if (\str_starts_with($line, 'webkernel-class ')) {
            $this->trace_push('classes', \substr($line, 16));

            return;
        }
        if (\str_starts_with($line, 'webkernel-mem ')) {
            $this->trace_push('mem', \substr($line, 14));

            return;
        }
        if (\str_starts_with($line, 'webkernel-type ')) {
            $this->trace_push('type', \substr($line, 15));

            return;
        }

        if (\preg_match('/\[(\d+)\]:\s+(\S+)\s+(\S+)/', $line, $m) === 1) {
            $status = (int) $m[1];
            $request = $m[2].' '.$m[3];
            $render_ms = null;
            if (\preg_match('/render=([0-9.]+)/', $line, $r) === 1) {
                $render_ms = (float) $r[1];
            }
            $request_ms = null;
            if (\preg_match('/request=([0-9.]+)/', $line, $r) === 1) {
                $request_ms = (float) $r[1];
            }
            if ($port !== null && isset($this->requests_pool[$port])) {
                $this->requests_pool[$port]['request'] = $request;
                $this->requests_pool[$port]['status'] = $status;
                if ($render_ms !== null) {
                    $this->requests_pool[$port]['render_ms'] = $render_ms;
                }
                if ($request_ms !== null) {
                    $this->requests_pool[$port]['request_ms'] = $request_ms;
                }

                return;
            }
            $this->log_request($request, $status, \hrtime(true), ['render_ms' => $render_ms]);

            return;
        }

        if (\str_ends_with($line, ' Closing') && $port !== null) {
            $entry = $this->requests_pool[$port] ?? null;
            unset($this->requests_pool[$port]);
            if ($entry === null || $entry['request'] === '') {
                return;
            }
            $this->log_request($entry['request'], $entry['status'], $entry['started'], [
                'render_ms' => $entry['render_ms'], 'times' => $entry['times'],
                'request_ms' => $entry['request_ms'],
                'classes' => $entry['classes'], 'mem' => $entry['mem'], 'type' => $entry['type'],
            ]);

            return;
        }

        if (\str_starts_with($line, '[')) {
            $line = \trim((string) \strstr($line, '] '));
        }
        if ($line !== '') {
            echo '  '.Terminal::muted($line)."\n";
        }
    }

    /**
     * @param string $line
     *
     * @return int|null Client port when the line is an Accept/Close/status event
     */
    private function request_port(string $line): ?int
    {
        if (\preg_match('/:(\d+)\s+(?:Accepted|Closing|\[\d+\])/', $line, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @return void
     */
    private function render_banner(): void
    {
        $banner_start_message = [
            'This feature is part of Webkernel.',
            '(c) 2025 - '.(\date('Y') + 1).' Numerimondes, El Moumen Yassine',
            'Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>',
            'For the full copyright and license information, please view the LICENSE',
            'file that was distributed with the source code.',
            'Made in Morocco.',
        ];
        echo "\n";
        foreach ($banner_start_message as $line) {
            echo Terminal::muted('  //> '.$line)."\n";
        }

        $display_host = \str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;
        $url = "http://{$display_host}:{$this->port}";
        $child = Status::inspect('cli-server');
        $ext = \extension_loaded('Zend OPcache');
        $opcache_active = $this->jit !== null ? $ext : $child->opcache;
        $jit_active = $this->jit === true ? $ext : ($this->jit === false ? false : $child->jit);
        $opcache_status = $opcache_active ? Terminal::GREEN.'enabled'.Terminal::RESET : Terminal::GRAY.'disabled'.Terminal::RESET;
        $jit_status = $jit_active ? Terminal::GREEN.'enabled'.Terminal::RESET : Terminal::GRAY.'disabled'.Terminal::RESET;
        echo "\n";
        console()->info('Server running on '.Terminal::BOLD . $url .Terminal::RESET);
        if ($this->mounts !== []) {
            echo "\n  ".Terminal::BOLD.'Applications:'.Terminal::RESET."\n";
            echo '    '.Terminal::badge('MAIN', '44', '97').' / → '.$this->public_dir."\n";
            foreach ($this->mounts as $prefix => $directory) {
                echo '    '.Terminal::badge($prefix, '45', '97').' '.$url.$prefix.' → '.$directory."\n";
            }
        }
        echo "\n";
        echo '  '.Terminal::muted('PHP Version: '.PHP_VERSION.' | OPcache: '.$opcache_status.' | JIT: '.$jit_status)."\n\n";
        if ($this->jit === true && ! \extension_loaded('Zend OPcache')) {
            console()->warning('Zend OPcache is not loaded; --with-jit has no effect.');
            echo "\n";
        }
        console()->warning('This server is for local development only. Behavior differs from production (Nginx/Apache/FPM).');
        echo "\n";
        if ($this->profile_lifecycle) {
            console()->info(Terminal::muted('--profile-lifecycle').' real include execute cost (hrtime). JSON: webkernel-profile-json');
            echo "\n";
        }
        echo '  '.Terminal::muted('Press Ctrl+C to stop the server')."\n\n";
    }

    /**
     * @param string         $request
     * @param int            $status_code
     * @param int|float      $start_time
     * @param RequestTimings $timings
     *
     * @return void
     */
    private function log_request(string $request, int $status_code, float|int $start_time, array $timings = []): void
    {
        if (! $this->is_first_request && $this->profile_lifecycle) {
            echo "\n";
        }
        $this->is_first_request = false;

        $request_ms = isset($timings['request_ms']) && \is_numeric($timings['request_ms'])
            ? (float) $timings['request_ms']
            : (\hrtime(true) - $start_time) / 1e6;
        $this->record_summary('request', $request_ms);
        if (isset($timings['render_ms']) && \is_numeric($timings['render_ms'])) {
            $this->record_summary('render', (float) $timings['render_ms']);
        }
        if (isset($timings['mem']) && \is_numeric($timings['mem'])) {
            $this->record_summary('memory', (float) $timings['mem']);
        }
        $this->status_counts[$status_code] = ($this->status_counts[$status_code] ?? 0) + 1;
        $timestamp = \date('H:i:s');
        $width = $this->line_width();

        $is_api = $timings['type'] ?? 'WEB';
        [$method, $uri] = $this->request_parts($request);
        $app = MountPaths::match($uri, $this->mounts);
        $type_badge = $app !== null
            ? Terminal::badge($app, '45', '97')
            : ($is_api === 'API' ? Terminal::badge('API', '45', '97') : Terminal::badge('WEB', '42', '97'));
        $reason = $this->status_reason($status_code);

        $right_plain = $this->metrics_plain($timings['render_ms'] ?? null, $request_ms);
        $right = Terminal::muted($right_plain);

        $prefix = \sprintf('  %s %s ', $type_badge, Terminal::muted($timestamp));
        $status = Terminal::status_color($status_code).$this->status_label($status_code, $reason, $width).Terminal::RESET;
        $method = Terminal::CYAN.\str_pad($method, 7).Terminal::RESET;
        echo $prefix.$status.' '.$method.Terminal::CYAN.$uri.Terminal::RESET.'  '.$right."\n";
        if (! $this->profile_lifecycle) {
            return;
        }

        $times = $timings['times'] ?? [];
        $classes = $timings['classes'] ?? [];
        $mem = $timings['mem'] ?? null;
        $mem_label = \is_numeric($mem) ? \number_format(((int) $mem) / 1048576, 1).'MB' : '?';

        $valid_times = [];
        foreach ($times as $row) {
            if ($row['ms'] !== null) {
                $valid_times[] = $row['ms'];
            }
        }
        \sort($valid_times);
        $count_valid = \count($valid_times);
        $median = 0.0;
        if ($count_valid > 0) {
            $mid = (int) \floor(($count_valid - 1) / 2);
            $median = ($count_valid % 2 === 0) ? ($valid_times[$mid] + $valid_times[$mid + 1]) / 2.0 : $valid_times[$mid];
        }

        $grades = ['e' => 0, 'g' => 0, 'f' => 0, 's' => 0];
        foreach ($valid_times as $t) {
            if ($t < 0.1) {
                $grades['e']++;
            } elseif ($t < 0.5) {
                $grades['g']++;
            } elseif ($t < 1.0) {
                $grades['f']++;
            } else {
                $grades['s']++;
            }
        }
        echo "\n";

        $summary1 = \sprintf('%s -> %d %s | files=%d timed=%d classes=%d mem=%s', $request, $status_code, $reason, \count($times), $count_valid, \count($classes), $mem_label);
        $summary2 = \sprintf('grades excellent=%d good=%d fair=%d slow=%d (%de %dg %df %ds) median=%.3fms', $grades['e'], $grades['g'], $grades['f'], $grades['s'], $grades['e'], $grades['g'], $grades['f'], $grades['s'], $median);

        echo '  '.Terminal::muted($summary1)."\n";
        echo '  '.Terminal::muted($summary2)."\n";

        if ($times === [] && $classes === []) {
            return;
        }

        echo "\n";
        echo '  '.Terminal::RESET.'  TOTAL     RUN     READ     FILE'.Terminal::RESET."\n\n";
        echo '  '.Terminal::RESET.' ---------------------------------'.Terminal::RESET."\n\n";
        foreach ($times as $row) {
            $ms = $row['ms'];
            $run = $row['run_ms'];
            $read = $row['read_ms'];
            $path = $row['path'];
            $flag = '';
            if ($ms !== null) {
                if ($median > 0.0) {
                    if ($ms > 2.0 * $median) {
                        $flag = ' '.Terminal::RED.'[SLOW] '.Terminal::RESET;
                    } elseif ($ms < 0.5 * $median) {
                        $flag = ' '.Terminal::GREEN.'[FAST] '.Terminal::RESET;
                    } else {
                        $flag = ' '.Terminal::GRAY.' [OK]  '.Terminal::RESET;
                    }
                } else {
                    $flag = ' '.Terminal::GRAY.' [OK]  '.Terminal::RESET;
                }
            }
            $ms_str = $ms === null ? '      ??' : \sprintf('%8.3f', $ms);
            $run_str = $run === null ? '      ??' : \sprintf('%8.3f', $run);
            $read_str = $read === null ? '      ??' : \sprintf('%8.3f', $read);

            echo \sprintf("  %s  %s  %s  %s%s\n", Terminal::muted($ms_str), Terminal::muted($run_str), Terminal::muted($read_str), $flag, Terminal::muted(' ->  ').$path);
        }
        echo "\n";
        foreach ($classes as $class) {
            echo '  '.Terminal::muted('class '.$class)."\n";
        }
    }

    /**
     * @param float|null $render_ms
     * @param float      $request_ms
     *
     * @return string
     */
    private function metrics_plain(?float $render_ms, float $request_ms): string
    {
        $request = 'request '.$this->format_milliseconds($request_ms);

        return $render_ms === null ? $request : 'render '.$this->format_milliseconds($render_ms).'  '.$request;
    }

    private function format_milliseconds(float $milliseconds): string
    {
        $width = self::METRIC_INTEGER_COLUMNS + 1 + self::METRIC_PRECISION;

        return \sprintf('%'.$width.'.'.self::METRIC_PRECISION.'fms', $milliseconds);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function request_parts(string $request): array
    {
        if (\preg_match('/^([A-Z]+)\s+(.+)$/', $request, $matches) === 1) {
            return [$matches[1], $matches[2]];
        }

        return ['', $request];
    }

    private function status_label(int $code, string $reason, int $line_width): string
    {
        $base_width = \strlen('304 '.$this->status_reason(304));
        $max_width = \max($base_width, \intdiv($line_width, 7));
        $width = \min($this->status_label_width(), $max_width);
        $label = $this->shorten($code.' '.$reason, $width);

        return \str_pad($label, $width);
    }

    /**
     * @return int
     */
    private function line_width(): int
    {
        return \max(60, (int) (Terminal::columns() * 0.95));
    }

    /**
     * @param string $text
     *
     * @return int Visible column count, ANSI sequences stripped
     */
    private function visible_len(string $text): int
    {
        $plain = \preg_replace('/\033\[[0-9;]*m/', '', $text);

        return \strlen(\is_string($plain) ? $plain : $text);
    }

    /**
     * @param string $text
     * @param int    $max
     *
     * @return string
     */
    private function shorten(string $text, int $max): string
    {
        if ($max < 2 || \strlen($text) <= $max) {
            return $text;
        }

        return \substr($text, 0, $max - 1).'...';
    }

    /**
     * @param int $code HTTP status code
     *
     * @return string
     */
    private function status_reason(int $code): string
    {
        if (\class_exists(\Webkernel\Errors\HttpError::class)) {
            try {
                return \Webkernel\Errors\HttpError::reason($code);
            } catch (\Throwable) {
                // Keep request logging alive when an optional error package is incomplete.
            }
        }

        return self::fallback_status_phrases()[$code] ?? 'Response';
    }

    private function status_label_width(): int
    {
        if (\class_exists(\Webkernel\Errors\HttpError::class)) {
            try {
                return \Webkernel\Errors\HttpError::status_label_width();
            } catch (\Throwable) {
                // Use the built-in list below when the optional catalogue cannot load.
            }
        }

        $phrases = self::fallback_status_phrases();
        $widths = \array_map(
            static fn (int $code, string $phrase): int => \strlen($code.' '.$phrase),
            \array_keys($phrases),
            $phrases,
        );

        return \max($widths);
    }

    /** @return array<int, string> */
    private static function fallback_status_phrases(): array
    {
        return [
            100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content', 206 => 'Partial Content',
            300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified',
            307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
            405 => 'Method Not Allowed', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone',
            413 => 'Payload Too Large', 415 => 'Unsupported Media Type', 418 => 'I\'m a Teapot',
            422 => 'Unprocessable Entity', 429 => 'Too Many Requests',
            500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway',
            503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported',
        ];
    }

    /**
     * @param IncludeRow $row
     *
     * @return void
     */
    private function trace_time(array $row): void
    {
        \end($this->requests_pool);
        $port = \key($this->requests_pool);
        if (! \is_int($port)) {
            return;
        }
        $this->requests_pool[$port]['times'][] = $row;
    }

    /**
     * @param string $kind
     * @param string $value
     *
     * @return void
     */
    private function trace_push(string $kind, string $value): void
    {
        \end($this->requests_pool);
        $port = \key($this->requests_pool);
        if (! \is_int($port)) {
            return;
        }
        if ($kind === 'mem') {
            $this->requests_pool[$port]['mem'] = (int) $value;

            return;
        }
        if ($kind === 'type') {
            $this->requests_pool[$port]['type'] = $value;

            return;
        }
        $this->requests_pool[$port][$kind][] = $value;
    }

    /**
     * @return void
     */
    private function register_signals(): void
    {
        if (! \function_exists('pcntl_async_signals') || ! \function_exists('pcntl_signal')) {
            return;
        }
        \pcntl_async_signals(true);
        $stop = function (int $signal): void {
            $this->interrupted = true;
            $this->interrupt_exit_code = 128 + $signal;
            $process = $this->process;
            if ($process !== null && \proc_get_status($process)['running']) {
                \proc_terminate($process, defined('SIGTERM') ? SIGTERM : $signal);
            }
        };
        \pcntl_signal(SIGINT, $stop);
        if (defined('SIGTERM')) {
            \pcntl_signal(SIGTERM, $stop);
        }
        if (defined('SIGHUP')) {
            \pcntl_signal(SIGHUP, $stop);
        }
        if (defined('SIGQUIT')) {
            \pcntl_signal(SIGQUIT, $stop);
        }
        \register_shutdown_function(function (): void {
            $this->close_process();
        });
    }

    private function record_summary(string $key, float $value): void
    {
        $metric = &$this->summary_metrics[$key];
        $metric['count']++;
        $metric['sum'] += $value;
        $metric['min'] = $metric['min'] === null ? $value : \min($metric['min'], $value);
        $metric['max'] = $metric['max'] === null ? $value : \max($metric['max'], $value);
    }

    private function print_summary(): void
    {
        $uptime_seconds = (\hrtime(true) - $this->server_started_at) / 1e9;
        $request_count = $this->summary_metrics['request']['count'];
        $requests_per_second = $uptime_seconds > 0.0 ? $request_count / $uptime_seconds : 0.0;
        echo "\n";
        console()->info('Server stopped by interrupt. Session summary:');
        echo '  '.Terminal::muted(\sprintf(
            'requests=%d · uptime=%.1fs · throughput=%.2f req/s',
            $request_count,
            $uptime_seconds,
            $requests_per_second,
        ))."\n";
        foreach (['render' => 'Render', 'request' => 'Request'] as $key => $label) {
            $metric = $this->summary_metrics[$key];
            if ($metric['count'] === 0) {
                echo '  '.Terminal::muted($label.': no measurements')."\n";
                continue;
            }
            $average = $metric['sum'] / $metric['count'];
            echo '  '.Terminal::muted(\sprintf(
                '%s (%d): min %.2f ms · avg %.2f ms · max %.2f ms',
                $label,
                $metric['count'],
                $metric['min'],
                $average,
                $metric['max'],
            ))."\n";
        }
        $memory = $this->summary_metrics['memory'];
        if ($memory['count'] > 0) {
            echo '  '.Terminal::muted(\sprintf(
                'Memory (%d profiled requests): min %.1f MB · avg %.1f MB · max %.1f MB',
                $memory['count'],
                $memory['min'] / 1048576,
                $memory['sum'] / $memory['count'] / 1048576,
                $memory['max'] / 1048576,
            ))."\n";
        }
        if ($this->status_counts !== []) {
            \ksort($this->status_counts);
            $statuses = [];
            foreach ($this->status_counts as $status => $count) {
                $statuses[] = $status.'='.$count;
            }
            echo '  '.Terminal::muted('HTTP statuses: '.\implode(' · ', $statuses))."\n";
        }
        echo "\n";
    }

    /**
     * @return void
     */
    private function close_process(): void
    {
        $process = $this->process;
        if ($process === null) {
            $this->pipes = [];

            return;
        }
        $status = \proc_get_status($process);
        if ($status['running']) {
            \proc_terminate($process, SIGTERM);
            $deadline = \microtime(true) + 2;
            while (\proc_get_status($process)['running'] && \microtime(true) < $deadline) {
                \usleep(50_000);
            }
            if (\proc_get_status($process)['running']) {
                \proc_terminate($process, SIGKILL);
            }
        }
        foreach ($this->pipes as $pipe) {
            \fclose($pipe);
        }
        $this->pipes = [];
        \proc_close($process);
        $this->process = null;
    }

    private static function valid_host(string $host): bool
    {
        return \filter_var($host, FILTER_VALIDATE_IP) !== false
            || \preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?\z/', $host) === 1;
    }
}
