<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Compile;

use RuntimeException;
use Webkernel\Route\Binding;
use Webkernel\Route\Route;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;

/**
 * Opcache-friendly compiled dispatcher file. Closures are dumped as source
 * so the hot path does not re-require route files or run Pattern/Generator.
 *
 * @internal
 *
 * @phpstan-import-type RouteData from Generator
 * @phpstan-type Payload array{compiled_at: int, host: string, files: array<string, int>, data: RouteData, hosts?: array<string, RouteData>, runtime?: array<string, mixed>}
 */
final class Cache
{
    private const DIRECTORY_PERMISSIONS = 0775;

    private const FILE_PERMISSIONS = 0664;

    /** @var Payload|null|false */
    private static array|null|false $memo = false;

    public static function path(): string
    {
        if (function_exists('base_path')) {
            $deployment_directory = DeploymentLock::artifactDirectory(base_path(), 'routes');
            if ($deployment_directory !== null) {
                return $deployment_directory.'/compiled_routes.php';
            }
        }

        return self::directory().'/compiled_routes.php';
    }

    public static function reset(): void
    {
        self::$memo = false;
    }

    public static function directory(): string
    {
        $cache_path = config('paths.cache', 'runtime/cache');

        if (! \is_string($cache_path) || $cache_path === '') {
            throw new \RuntimeException('The configured cache path is invalid.');
        }

        return base_path($cache_path);
    }

    /**
     * @return Payload|null
     */
    public static function payload(): ?array
    {
        if (self::$memo !== false) {
            return self::$memo;
        }
        $path = self::path();
        if (! \is_file($path)) {
            return self::$memo = null;
        }
        $value = include $path;
        if (! \is_array($value) || ! isset($value['data']) || ! \is_array($value['data'])) {
            return self::$memo = null;
        }
        /** @var Payload $value */
        return self::$memo = $value;
    }

    /**
     * Dump files plus the route PHP they list. SVG/view mtimes are not
     * route fingerprints — views have their own compiled mtime check.
     *
     * @return array<string, int>
     */
    public static function fingerprints(): array
    {
        $out = [];
        foreach (self::source_files() as $file) {
            $mtime = @\filemtime($file);
            if (\is_int($mtime)) {
                $out[$file] = $mtime;
            }
        }
        \ksort($out);

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function source_files(): array
    {
        $files = [];
        $configured_routes = Route::configuredRouteFiles();
        if ($configured_routes !== null) {
            $files = [...$files, ...$configured_routes, ...(Route::configuredRouteDependencies() ?? [])];
        }
        $routes_dump = $configured_routes === null && \function_exists('base_path')
            ? \base_path('runtime/cache/declarations/webkernel_routes.php')
            : '';
        $panel_dump = \function_exists('base_path') ? \base_path('runtime/cache/declarations/webkernel_panels.php') : '';
        foreach ([$routes_dump, $panel_dump] as $dump) {
            if ($dump !== '' && \is_file($dump)) {
                $files[] = $dump;
            }
        }
        if ($routes_dump !== '' && \is_file($routes_dump)) {
            $listed = include $routes_dump;
            if (\is_array($listed)) {
                foreach ($listed as $path) {
                    if (\is_string($path) && $path !== '' && \is_file($path)) { $files[] = $path; }
                }
            }
        }
        if ($panel_dump !== '' && \is_file($panel_dump)) {
            $providers = include $panel_dump;
            if (is_array($providers)) {
                foreach ($providers as $provider) {
                    if (is_string($provider) && class_exists($provider)) {
                        $source = (new \ReflectionClass($provider))->getFileName();
                        if (is_string($source) && is_file($source)) { $files[] = $source; }
                    }
                }
            }
        }
        $files[] = __FILE__;
        return \array_values(\array_unique($files));
    }

    public static function is_fresh(): bool
    {
        $payload = self::payload();
        if (function_exists('base_path') && DeploymentLock::read(base_path()) !== null) {
            if ($payload === null || ! isset($payload['data']) || ! is_array($payload['data'])) {
                throw new RuntimeException('The deployed route artifact is invalid. Run ./platform deploy.');
            }

            return true;
        }
        if ($payload === null) {
            return false;
        }
        $stored = $payload['files'] ?? [];
        if (! \is_array($stored) || $stored === []) {
            return false;
        }
        if ($stored !== self::fingerprints()) {
            return false;
        }
        foreach ($stored as $file => $mtime) {
            if (! \is_string($file) || ! \is_int($mtime)) {
                return false;
            }
            if (@\filemtime($file) !== $mtime) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return RouteData|null
     */
    public static function fresh_data(string $host = ''): ?array
    {
        if (! self::is_fresh()) {
            return null;
        }
        $payload = self::payload();
        $data = $payload['data'] ?? null;
        foreach (($payload['hosts'] ?? []) as $domain => $host_data) {
            if (! is_string($domain) || ! is_array($host_data)) {
                continue;
            }
            $matches = str_contains($domain, '{')
                ? preg_match(Binding::domain_regex($domain), $host) === 1
                : strcasecmp($domain, $host) === 0;
            if ($matches) {
                $data = $host_data;
                break;
            }
        }

        return \is_array($data) ? $data : null;
    }

    /**
     * @return RouteData|null
     */
    public static function read(?string $path = null): ?array
    {
        if ($path !== null && $path !== self::path()) {
            if (! \is_file($path)) {
                return null;
            }
        $value = include $path;

            return \is_array($value) ? $value : null;
        }
        $payload = self::payload();

        return $payload['data'] ?? null;
    }

    /**
     * @param RouteData $data
     * @param array{compiled_at?: int, host?: string, files?: array<string, int>, hosts?: array<string, RouteData>, runtime?: array<string, mixed>} $meta
     */
    public static function write(string $path, array $data, array $meta = []): void
    {
        $directory = \dirname($path);
        if (! \is_dir($directory) && ! \mkdir($directory, self::DIRECTORY_PERMISSIONS, true) && ! \is_dir($directory)) {
            return;
        }
        if (! \is_writable($directory)) {
            return;
        }

        $payload = [
            'compiled_at' => $meta['compiled_at'] ?? \time(),
            'host' => $meta['host'] ?? '',
            'files' => $meta['files'] ?? [],
            'data' => $data,
        ];
        if (isset($meta['hosts'])) {
            $payload['hosts'] = $meta['hosts'];
        }
        if (isset($meta['runtime'])) {
            $payload['runtime'] = $meta['runtime'];
        }
        $tmp = $path.'.'.\getmypid().'.tmp';
        $body = "<?php declare(strict_types=1);\n\nreturn ".self::export($payload).";\n";
        if (\file_put_contents($tmp, $body) === false) {
            return;
        }
        \chmod($tmp, self::FILE_PERMISSIONS);
        if (! \rename($tmp, $path)) {
            @\unlink($tmp);

            return;
        }
        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate($path, true);
        }
        self::$memo = $payload;
    }

    private static function export(mixed $value): string
    {
        if ($value instanceof \Closure) {
            return self::export_closure($value);
        }
        if (\is_object($value)) {
            return self::export_object($value);
        }
        if (\is_array($value)) {
            if ($value === []) {
                return '[]';
            }
            $is_list = \array_is_list($value);
            $parts = [];
            foreach ($value as $key => $item) {
                $exported = self::export($item);
                $parts[] = $is_list ? $exported : \var_export($key, true).' => '.$exported;
            }

            return '['.\implode(', ', $parts).']';
        }

        return \var_export($value, true);
    }

    private static function export_object(object $value): string
    {
        if (! \method_exists($value, '__set_state')) {
            throw new \RuntimeException('Cannot compile route handler of type '.$value::class.'.');
        }
        $vars = \get_object_vars($value);

        return '\\'.$value::class.'::__set_state('.self::export($vars).')';
    }

    /** @param $closure */
    private static function export_closure(\Closure $closure): string
    {
        $ref = new \ReflectionFunction($closure);
        if ($ref->getStaticVariables() !== []) {
            throw new \RuntimeException('Cannot compile route closures that bind variables.');
        }
        $file = $ref->getFileName();
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        if (! \is_string($file) || ! \is_file($file) || $start < 1 || $end < $start) {
            throw new \RuntimeException('Cannot compile eval route closures.');
        }
        $lines = \file($file);
        if ($lines === false) {
            throw new \RuntimeException('Cannot read route file for compilation: '.$file);
        }
        $chunk = \implode('', \array_slice($lines, $start - 1, $end - $start + 1));
        $source = self::extract_closure_source($chunk);
        if ($source === '') {
            throw new \RuntimeException('Cannot extract route closure source from '.$file.':'.$start);
        }
        $source = self::resolve_imported_names($source, self::imports_before_line($file, $start));

        return $source;
    }

    /**
     * @return array<string, string>
     */
    private static function imports_before_line(string $file, int $line): array
    {
        $lines = \file($file);
        if ($lines === false) {
            throw new \RuntimeException('Cannot read route file imports from '.$file.'.');
        }

        $prefix = \implode('', \array_slice($lines, 0, $line - 1));
        \preg_match_all('/^[ \t]*use[ \t]+([^;()]+);/m', $prefix, $matches);
        $imports = [];

        foreach ($matches[1] ?? [] as $declaration) {
            $declaration = \trim($declaration);
            if ($declaration === '' || \preg_match('/\A(?:function|const)\b/i', $declaration) === 1) {
                continue;
            }

            $group_prefix = '';
            if (\preg_match('/\A(.+?)\\\\\{(.+)\}\z/', $declaration, $group) === 1) {
                $group_prefix = \rtrim(\trim($group[1]), '\\\\').'\\';
                $declaration = $group[2];
            }

            foreach (\explode(',', $declaration) as $item) {
                if (\preg_match('/\A\\\\?([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff\\\\]*)(?:\s+as\s+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*))?\z/i', \trim($item), $import) !== 1) {
                    continue;
                }

                $name = $group_prefix.$import[1];
                $segments = \explode('\\', $name);
                $alias = $import[2] ?? \end($segments);
                $imports[\strtolower($alias)] = '\\'.\ltrim($name, '\\');
            }
        }

        return $imports;
    }

    /**
     * Resolve imported class aliases before the closure is written to the
     * global compiled file, where the route file's use declarations no longer apply.
     *
     * @param array<string, string> $imports
     */
    private static function resolve_imported_names(string $source, array $imports): string
    {
        if ($imports === []) {
            return $source;
        }

        $tokens = \token_get_all('<?php '.$source);
        $resolved = '';
        $count = \count($tokens);
        for ($index = 1; $index < $count; $index++) {
            $token = $tokens[$index];
            if (! \is_array($token) || $token[0] !== T_STRING) {
                $resolved .= \is_array($token) ? $token[1] : $token;
                continue;
            }

            $import = $imports[\strtolower($token[1])] ?? null;
            if ($import === null) {
                $resolved .= $token[1];
                continue;
            }

            $previous = self::previous_code_token($tokens, $index);
            $next_index = self::next_code_token($tokens, $index + 1);
            $next = $next_index === null ? null : $tokens[$next_index];
            $previous_id = \is_array($previous) ? $previous[0] : null;
            $previous_text = \is_array($previous) ? $previous[1] : $previous;
            $next_text = \is_array($next) ? $next[1] : $next;

            if (
                \in_array($previous_id, [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_FN], true)
                || (\defined('T_NULLSAFE_OBJECT_OPERATOR') && $previous_id === T_NULLSAFE_OBJECT_OPERATOR)
                || ($next_text === '(' && $previous_id !== T_NEW)
                || $next_text === ':'
                || $previous_text === '\\'
            ) {
                $resolved .= $token[1];
                continue;
            }

            $resolved .= $import;
        }

        return $resolved;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private static function previous_code_token(array $tokens, int $from): array|string|null
    {
        for ($index = $from - 1; $index >= 0; $index--) {
            $token = $tokens[$index];
            if (\is_array($token) && \in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    private static function extract_closure_source(string $chunk): string
    {
        $tokens = \token_get_all('<?php '.$chunk);
        $count = \count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (! \is_array($token)) {
                continue;
            }
            $static = $token[0] === T_STATIC;
            $fn_index = $static ? self::next_code_token($tokens, $i + 1) : $i;
            if ($fn_index === null) {
                continue;
            }
            $fn = $tokens[$fn_index];
            if (! \is_array($fn) || ($fn[0] !== T_FN && $fn[0] !== T_FUNCTION)) {
                continue;
            }
            if ($fn[0] === T_FUNCTION && self::closure_has_use($tokens, $fn_index)) {
                throw new \RuntimeException('Cannot compile route closures that bind variables.');
            }
            $end = $fn[0] === T_FN
                ? self::end_of_arrow($tokens, $fn_index)
                : self::end_of_function($tokens, $fn_index);
            if ($end === null) {
                return '';
            }

            return self::join_tokens($tokens, $static ? $i : $fn_index, $end);
        }

        return '';
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function next_code_token(array $tokens, int $from): ?int
    {
        $count = \count($tokens);
        for ($i = $from; $i < $count; $i++) {
            $token = $tokens[$i];
            if (\is_array($token) && ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function closure_has_use(array $tokens, int $fn_index): bool
    {
        $count = \count($tokens);
        for ($i = $fn_index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === '{' || (\is_array($token) && $token[0] === T_CURLY_OPEN)) {
                return false;
            }
            if (\is_array($token) && $token[0] === T_USE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function end_of_arrow(array $tokens, int $fn_index): ?int
    {
        $count = \count($tokens);
        $seen_arrow = false;
        $paren = 0;
        $bracket = 0;
        $brace = 0;
        for ($i = $fn_index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            if (! $seen_arrow) {
                if (\is_array($token) && $token[0] === T_DOUBLE_ARROW) {
                    $seen_arrow = true;
                }
                continue;
            }
            if (\is_array($token)) {
                if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                continue;
            }
            if ($token === '(') {
                $paren++;
                continue;
            }
            if ($token === '[') {
                $bracket++;
                continue;
            }
            if ($token === '{') {
                $brace++;
                continue;
            }
            if ($token === ')') {
                if ($paren === 0 && $bracket === 0 && $brace === 0) {
                    return $i - 1;
                }
                $paren--;
                continue;
            }
            if ($token === ']') {
                if ($paren === 0 && $bracket === 0 && $brace === 0) {
                    return $i - 1;
                }
                $bracket--;
                continue;
            }
            if ($token === '}') {
                if ($paren === 0 && $bracket === 0 && $brace === 0) {
                    return $i - 1;
                }
                $brace--;
                continue;
            }
            if (($token === ',' || $token === ';') && $paren === 0 && $bracket === 0 && $brace === 0) {
                return $i - 1;
            }
        }

        return $seen_arrow ? $count - 1 : null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function end_of_function(array $tokens, int $fn_index): ?int
    {
        $count = \count($tokens);
        $brace = 0;
        $started = false;
        for ($i = $fn_index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            $char = \is_array($token) ? '' : $token;
            if ($char === '{') {
                $brace++;
                $started = true;
                continue;
            }
            if ($char === '}') {
                $brace--;
                if ($started && $brace === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function join_tokens(array $tokens, int $from, int $to): string
    {
        $out = '';
        for ($i = $from; $i <= $to; $i++) {
            $token = $tokens[$i];
            $out .= \is_array($token) ? $token[1] : $token;
        }

        return \trim($out);
    }
}
