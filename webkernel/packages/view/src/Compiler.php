<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use Webkernel\View\Compile\Directives;
use Webkernel\View\Compile\Pipeline;
use Webkernel\View\Compile\State;

/**
 * Compile-time only. Engine includes the compiled PHP. This class is not
 * loaded on a cache hit.
 */
final class Compiler
{
    private static ?int $last_modified_at = null;

    /** @var list<callable(string, string): string> */
    private array $precompilers = [];

    private ?string $inline_cache_revision = null;

    private ?string $source_path = null;

    private array $anonymous_component_paths = [];

    private array $anonymous_component_namespaces = [];

    /**
     * @param $directives Directives
     * @param $echo_format string
     * @param $acl_module string|null
     */
    public function __construct(
        private readonly Directives $directives,
        private string $echo_format = '\\'.View::class.'::echo(%s)',
        private ?string $acl_module = null,
    ) {
    }

    /**
     * @param $format string
     *
     * @return void
     */
    public function set_echo_format(string $format): void
    {
        $this->echo_format = $format;
    }

    /**
     * @param $module string|null
     *
     * @return void
     */
    public function set_acl_module(?string $module): void
    {
        $this->acl_module = $module;
    }

    /**
     * @return Directives
     */
    public function directives(): Directives
    {
        return $this->directives;
    }

    /**
     * @param $name string
     * @param $handler callable(string, State): string
     *
     * @return void
     */
    public function directive(string $name, callable $handler): void
    {
        $this->directives->handler($name, $handler);
        $this->inline_cache_revision = null;
    }

    /** Register source transforms that run before Webkernel compiles a view file. */
    public function prepareViewsForCompilationUsing(callable $callback): void
    {
        $this->precompilers[] = $callback;
        $this->inline_cache_revision = null;
    }

    /**
     * @param $name string
     * @param $callback callable(mixed...): bool
     *
     * @return void
     */
    public function register_if_statement(string $name, callable $callback): void
    {
        $this->directives->condition($name, $callback);
    }

    /**
     * @param $source string
     *
     * @return string
     */
    public function compile_string(string $source, ?string $source_path = null): string
    {
        $this->source_path = $source_path;
        $source_path ??= 'inline-'.hash('sha256', $source);
        $source = $this->prepare($source, $source_path);

        return Pipeline::compile($source, new State(
            $this->echo_format,
            $this->directives,
            $this->acl_module,
            $source_path,
        ));
    }

    /** Return a stable cache key that changes when compiler or directive registrations change. */
    public function inline_cache_key(string $source): string
    {
        $this->inline_cache_revision ??= hash('sha256', implode(':', [
            (string) self::lastModifiedAt(),
            (string) $this->directives->revision(),
            (string) count($this->precompilers),
        ]));

        return hash('sha256', $this->inline_cache_revision."\0".$source);
    }

    /** Returns the newest compiler source timestamp once per request for view-cache invalidation. */
    public static function lastModifiedAt(): int
    {
        if (self::$last_modified_at !== null) {
            return self::$last_modified_at;
        }

        $source_files = [
            __FILE__,
            __DIR__.'/Compile/Comments.php',
            __DIR__.'/Compile/Components.php',
            __DIR__.'/Compile/Directives.php',
            __DIR__.'/Compile/Echoes.php',
            __DIR__.'/Compile/Php.php',
            __DIR__.'/Compile/Pipeline.php',
            __DIR__.'/Compile/State.php',
            __DIR__.'/Compile/Statements.php',
            __DIR__.'/Markdown.php',
        ];
        $last_modified_at = 0;
        foreach ($source_files as $source_file) {
            $modified_at = filemtime($source_file);
            if (is_int($modified_at)) {
                $last_modified_at = max($last_modified_at, $modified_at);
            }
        }

        return self::$last_modified_at = $last_modified_at;
    }

    /**
     * @param $source_path string
     * @param $compiled_path string
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function compile_file(string $source_path, string $compiled_path): void
    {
        $source = file_get_contents($source_path);
        if (! is_string($source)) {
            throw new \RuntimeException('Unable to read '.$source_path);
        }
        $dir = dirname($compiled_path);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Unable to create '.$dir);
        }
        $temporary_path = \tmp_path('view-'.\bin2hex(\random_bytes(8)).'.tmp');
        if (file_put_contents($temporary_path, $this->compile_string($source, $source_path), LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write compiled view temporary file.');
        }
        if (! rename($temporary_path, $compiled_path)) {
            @unlink($temporary_path);
            throw new \RuntimeException('Unable to publish compiled view: '.$compiled_path);
        }
        \clearstatcache(true, $compiled_path);
        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate($compiled_path, true);
        }
    }

    private function prepare(string $source, string $source_path): string
    {
        foreach ($this->precompilers as $precompiler) {
            $prepared = $precompiler($source, $source_path);
            if (! is_string($prepared)) {
                throw new \RuntimeException('A view precompiler must return a string.');
            }
            $source = $prepared;
        }

        return $source;
    }

    public function compileString(string $source): string
    {
        return $this->compile_string($source);
    }

    public function compileEchos(string $source): string
    {
        return \Webkernel\View\Compile\Echoes::compile($source, $this->echo_format);
    }

    public function precompiler(callable $callback): void
    {
        $this->prepareViewsForCompilationUsing($callback);
    }

    public function getCustomDirectives(): array
    {
        return $this->directives->all();
    }

    public function replaceCustomDirectives(array $directives): void
    {
        $this->directives->replace($directives);
        $this->inline_cache_revision = null;
    }

    public function getPrecompilers(): array
    {
        return $this->precompilers;
    }

    public function replacePrecompilers(array $precompilers): void
    {
        foreach ($precompilers as $precompiler) {
            if (! is_callable($precompiler)) {
                throw new \InvalidArgumentException('A view precompiler must be callable.');
            }
        }
        $this->precompilers = array_values($precompilers);
        $this->inline_cache_revision = null;
    }

    public static function sanitizeComponentAttribute(mixed $value): mixed
    {
        return is_string($value) || (is_object($value) && $value instanceof \Stringable && ! $value instanceof \Webkernel\Utilities\Contracts\Htmlable)
            ? e($value)
            : $value;
    }

    public function getPath(): ?string
    {
        return $this->source_path;
    }

    public function anonymousComponentPath(string $path, ?string $prefix = null): void
    {
        $this->anonymous_component_paths[] = ['path' => $path, 'prefix' => $prefix, 'prefixHash' => hash('sha256', $path)];
        View::addNamespace($prefix ?? 'global', $path, components: true);
    }

    public function getAnonymousComponentPaths(): array { return $this->anonymous_component_paths; }

    public function getAnonymousComponentNamespaces(): array { return $this->anonymous_component_namespaces; }
}
