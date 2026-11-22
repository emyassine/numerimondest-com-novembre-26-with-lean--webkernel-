<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use Webkernel\ServiceProvider\Deployment\DeploymentLock;

use Closure;
use RuntimeException;
use Webkernel\View\Compile\Directives;
use Webkernel\View\Compile\Mode;
use Webkernel\View\RenderHooks;
use Webkernel\View\Support\TemplateLocator;

/**
 * Runtime for compiled views. Compiler is loaded only on compile miss.
 */
final class Engine
{

    private const PARENT_KEY = '@parentXYZABC';

    /** @var array<string, mixed> */
    public array $variables = [];

    /** @var array<string, mixed> */
    private array $variables_global = [];

    /** @var array<string, string> */
    private array $sections = [];

    /** @var list<string> */
    private array $section_stack = [];

    /** @var array<string, array<int, string>> */
    private array $pushes = [];

    /** @var list<string> */
    private array $push_stack = [];

    private int $render_count = 0;

    /** @var array<string, string> */
    private array $compiled_files = [];

    /** @var array<string, string> */
    private array $compiled_map = [];

    /** @var array<string, bool> */
    private array $fresh_views = [];

    /**
     * Component whose view is being rendered.
     *
     * Blade binds `$this` to the component in a component view; the engine keeps
     * that contract by forwarding unknown members to this object.
     */
    private ?object $view_component = null;

    private readonly TemplateLocator $templates;

    /** @var array<string, true> */
    private array $once = [];

    /** @var list<string> */
    private array $component_stack = [];

    /** @var array<int, array<string, mixed>> */
    private array $component_data = [];

    /** @var array<int, array<string, mixed>> */
    private array $slots = [];

    /** @var array<int, list<string>> */
    private array $slot_stack = [];

    /** @var array<int, RenderHooks> */
    private array $render_hooks = [];

    /** @var list<array{view: string, template: string}> */
    private array $render_stack = [];

    /** @var list<array{view: string, started_at: int, child_ms: float}> */
    private array $profile_evaluation_stack = [];

    /** @var list<array{iteration: int, index: int, remaining: ?int, count: ?int, first: bool, last: ?bool, odd: bool, even: bool, depth: int, parent: ?object}> */
    private array $loops = [];

    /** @var list<string> */
    private array $namespace_overrides = [];

    private string $echo_format = '\\'.View::class.'::echo(%s)';

    private ?Compiler $compiler = null;

    private int $directive_revision = 0;

    private readonly Directives $directives;

    /**
     * @param $template_path list<string>
     * @param $compile_dpath string
     * @param $mode Mode
     */
    public function __construct(
        array $template_path,
        private readonly string $compile_dpath,
        private readonly Mode $mode = Mode::Auto,
    ) {
        $this->directives = new Directives();
        $this->templates = new TemplateLocator($template_path);
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function run(string $view, array $variables = []): string
    {
        $this->loops = [];
        $this->once = [];
        $this->namespace_overrides = [];
        $this->sections = [];
        $this->pushes = [];
        $this->push_stack = [];
        $this->variables = $this->variables_global === []
            ? $variables
            : array_merge($variables, $this->variables_global);
        $buffer_level = ob_get_level();
        ob_start();
        try {
            $this->include_view($view);
        } catch (\Throwable $e) {
            $this->loops = [];
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }
            $this->component_stack = [];
            $this->component_data = [];
            $this->slots = [];
            $this->slot_stack = [];
            $this->render_hooks = [];
            $this->namespace_overrides = [];
            throw $e;
        }

        return ltrim((string) ob_get_clean());
    }

    /**
     * Render one View as HTML without resetting engine state.
     *
     * @param $view string
     * @param $variables array<string, mixed>
     *
     * @return string
     */
    public function html(string $view, array $variables = []): string
    {
        $backup = $this->variables;
        $this->variables = $this->variables_global === []
            ? $variables
            : array_merge($this->variables_global, $variables);
        ob_start();
        try {
            $this->include_view($view);
        } catch (\Throwable $e) {
            ob_get_clean();
            $this->variables = $backup;
            throw $e;
        }
        $html = (string) ob_get_clean();
        $this->variables = $backup;

        return $html;
    }

    /** Compile and render a trusted inline template fragment. */
    public function render_string(string $source, array $variables = []): string
    {
        $backup = $this->variables;
        $this->variables = $this->variables_global === []
            ? $variables
            : array_merge($this->variables_global, $variables);
        $compiler = $this->compiler();
        $source_key = $compiler->inline_cache_key($source);
        $inline_directory = rtrim($this->compile_dpath, '/\\').'/inline';
        $compiled_path = $inline_directory.'/'.$source_key.'.php';
        if (! is_file($compiled_path)) {
            if (! is_dir($inline_directory) && ! mkdir($inline_directory, 0775, true) && ! is_dir($inline_directory)) {
                throw new RuntimeException('Unable to create inline view cache directory.');
            }
            $temporary_path = \tmp_path('view-inline-'.\bin2hex(\random_bytes(8)).'.tmp');
            $compiled_source = $compiler->compile_string($source, 'inline-'.$source_key);
            if (file_put_contents($temporary_path, $compiled_source, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write inline view cache temporary file.');
            }
            if (! rename($temporary_path, $compiled_path)) {
                @unlink($temporary_path);
                if (! is_file($compiled_path)) {
                    throw new RuntimeException('Unable to publish compiled inline view.');
                }
            }
            \clearstatcache(true, $compiled_path);
            if (\function_exists('opcache_invalidate')) { \opcache_invalidate($compiled_path, true); }
        }
        $buffer_level = ob_get_level();
        ob_start();
        try {
            $this->evaluate($compiled_path, $this->variables);
            $html = (string) ob_get_clean();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }
            throw $exception;
        } finally {
            $this->variables = $backup;
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function run_child(string $view, array $variables = []): string
    {
        $view = $this->qualify_view($view);
        $backup = $this->variables;
        if ($variables !== []) {
            $this->variables = array_merge($this->variables, $variables);
        }
        try {
            $this->include_view($view);
        } finally {
            $this->variables = $backup;
        }

        return '';
    }

    /** Includes inherit the caller's local template variables; explicit data takes precedence. */
    public function includeChild(array $scope, string $view, array $data = []): string
    {
        foreach ($scope as $name => $_) {
            if (str_starts_with($name, '__') || $name === '_shouldextend') { unset($scope[$name]); }
        }
        return $this->run_child($view, array_replace($scope, $data));
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function run_component_child(string $view, array $variables): void
    {
        $backup = $this->variables;
        $this->variables = $this->variables_global === []
            ? $variables
            : array_merge($this->variables_global, $variables);

        try {
            $this->include_view($view);
        } finally {
            $this->variables = $backup;
        }
    }

    public function start_section(string $section, string $content = ''): void
    {
        if ($content === '') {
            ob_start() && $this->section_stack[] = $section;

            return;
        }
        $this->extend_section($section, $content);
    }

    /** Seed a named layout section before rendering a nested layout view. */
    public function set_section(string $section, string $content): void
    {
        $this->extend_section($section, $content);
    }

    public function stop_section(bool $overwrite = false): string
    {
        $last = array_pop($this->section_stack);
        if (! is_string($last) || $last === '') {
            throw new \RuntimeException('Cannot end a section without first starting one.');
        }
        $chunk = (string) ob_get_clean();
        if ($overwrite) {
            $this->sections[$last] = $chunk;
        } else {
            $this->extend_section($last, $chunk);
        }

        return $last;
    }

    public function yield_content(string $section, string $default = ''): string
    {
        if (! isset($this->sections[$section])) {
            return $default;
        }

        return str_replace(self::PARENT_KEY, $default, $this->sections[$section]);
    }

    public function start_push(string $section, string $content = ''): void
    {
        if ($content === '') {
            if (ob_start()) {
                $this->push_stack[] = $section;
            }

            return;
        }
        $this->extend_push($section, $content);
    }

    public function stop_push(): string
    {
        $last = array_pop($this->push_stack);
        if (! is_string($last) || $last === '') {
            throw new \RuntimeException('Cannot end a section without first starting one.');
        }
        $this->extend_push($last, (string) ob_get_clean());

        return $last;
    }

    /**
     * @param $section string
     * @param $default mixed
     *
     * @return string
     */
    public function stack(string $section, mixed $default = ''): string
    {
        return $this->yield_push_content($section, $default);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function start_component(string $name, array $data = []): void
    {
        $name = $this->qualify_view($name);
        $this->begin_component($name, $data);
    }

    /** Start a globally resolved unqualified component, such as `<x-webkernel::icon>`. */
    public function start_global_component(string $name, array $data = []): void
    {
        $this->begin_component('global::'.$name, $data);
    }

    /** @param array<string, mixed> $data */
    private function begin_component(string $name, array $data): void
    {
        if (ob_start()) {
            $this->component_stack[] = $name;
            $i = count($this->component_stack) - 1;
            $this->component_data[$i] = $data;
            $this->slots[$i] = [];
            $this->slot_stack[$i] = [];
        }
    }

    public function slot(string $name, mixed $content = null): void
    {
        $i = count($this->component_stack) - 1;
        if ($i < 0) {
            return;
        }
        if (func_num_args() === 2) {
            if ($content instanceof Closure) {
                $namespace = $this->current_namespace();
                $slot = $content;
                $content = function () use ($slot, $namespace): string {
                    $this->namespace_overrides[] = $namespace;
                    try {
                        return (string) $slot();
                    } finally {
                        array_pop($this->namespace_overrides);
                    }
                };
            }
            $this->slots[$i][$name] = $content;

            return;
        }
        if (ob_start()) {
            $this->slots[$i][$name] = '';
            $this->slot_stack[$i][] = $name;
        }
    }

    public function end_slot(): void
    {
        $i = count($this->component_stack) - 1;
        if ($i < 0) {
            return;
        }
        $name = array_pop($this->slot_stack[$i]);
        if (! is_string($name) || $name === '') {
            throw new \RuntimeException('Cannot end a slot without first starting one.');
        }
        $this->slots[$i][$name] = trim((string) ob_get_clean());
    }

    /**
     * @param $id string
     * @param $content Closure(array<string, mixed>): void
     * @param $scope array<string, mixed>
     * @param $variant string
     *
     * @return void
     * @throws RuntimeException
     */
    public function defineRenderHook(string $id, Closure $content, array $scope, string $variant = 'default'): void
    {
        $index = count($this->component_stack) - 1;
        if ($index < 0) {
            throw new RuntimeException('Render hook definitions require an enclosing component.');
        }
        ($this->render_hooks[$index] ??= new RenderHooks())->define($id, $content, $scope, $variant);
    }

    /**
     * @return string
     */
    public function render_component(): string
    {
        $name = array_pop($this->component_stack);
        $cs = count($this->component_stack);
        $data = $this->component_data[$cs] ?? [];
        $hooks = $this->render_hooks === [] ? null : end($this->render_hooks);
        if ($hooks instanceof RenderHooks) {
            $data['renderhooks'] = $hooks;
        }
        $slots = $this->slots[$cs] ?? [];
        $attribute_spreads = $data['__attribute_spreads'] ?? [];
        unset($data['__attribute_spreads']);
        $buffered_slot = trim((string) ob_get_clean());
        if (! array_key_exists('slot', $slots)) {
            $slots['slot'] = $buffered_slot;
        } elseif (is_string($slots['slot']) && $buffered_slot !== '') {
            $slots['slot'] .= $buffered_slot;
        }
        foreach ($slots as $slot_name => $slot_content) {
            if (is_string($slot_content)) {
                $slots[$slot_name] = new ComponentSlot($slot_content);
            }
        }
        $bag = [];
        foreach ($data as $key => $value) {
            if ($key === 'slot' || $key === 'attributes' || $key === 'renderhooks' || array_key_exists($key, $slots)) {
                continue;
            }
            // Bound component props may be arrays or objects; @props consumes them
            // in the child template, so they are not HTML attributes.
            if (! is_scalar($value) && $value !== null && ! ($key === 'class' && is_array($value))) {
                continue;
            }
            $bag[$key] = $value;
        }
        foreach ($attribute_spreads as $spread) {
            $bag = (new AttributeBag($bag))->merge(AttributeBag::normalize($spread))->to_array();
        }
        $cd = array_merge($data, $slots, [
            'attributes' => new AttributeBag($bag),
        ]);
        //> Rendered component templates inherit their own scope, not pending caller frames.
        $pending_hooks = $this->render_hooks;
        unset($pending_hooks[$cs]);
        $this->render_hooks = [];
        try {
            $this->run_component_child((string) $name, $cd);
        } finally {
            $this->render_hooks = $pending_hooks;
            unset($this->component_data[$cs], $this->slots[$cs], $this->slot_stack[$cs]);
        }

        return '';
    }

    /**
     * @param string|array<string, mixed> $varname
     */
    public function share(string|array $varname, mixed $value = null): self
    {
        if (is_array($varname)) {
            $this->variables_global = array_merge($this->variables_global, $varname);
            $this->variables = array_merge($this->variables, $varname);
        } else {
            $this->variables_global[$varname] = $value;
            $this->variables[$varname] = $value;
        }

        return $this;
    }

    public function forget(string $varname): self
    {
        unset($this->variables_global[$varname], $this->variables[$varname]);

        return $this;
    }

    public function isRendering(): bool { return $this->render_stack !== []; }

    public function componentView(string $name, string $file): void
    {
        $this->templates->alias($name, $file);
        unset($this->compiled_files[$name], $this->fresh_views[$name]);
    }

    public function add_template_path(string $path): void
    {
        $this->templates->add('', $path);
        $this->compiled_files = [];
    }

    public function add_view_namespace(string $namespace, string $path): void
    {
        $this->templates->add($namespace, $path);
        $this->compiled_files = [];
        $this->fresh_views = [];
    }

    public function add_component_namespace(string $namespace, string $path): void
    {
        $this->templates->add($namespace, $path);
        $this->compiled_files = [];
        $this->fresh_views = [];
    }

    /**
     * First call for $id returns true and records it. Later calls return false.
     *
     * @param $id string
     *
     * @return bool
     */
    public function once(string $id): bool
    {
        if (isset($this->once[$id])) {
            return false;
        }
        $this->once[$id] = true;

        return true;
    }

    /** Start a Blade-compatible loop context for a compiled @foreach/@forelse. */
    public function addLoop(mixed $data): void
    {
        $count = is_countable($data) ? count($data) : null;
        $parent = $this->getLastLoop();

        $this->loops[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $count,
            'count' => $count,
            'first' => true,
            'last' => $count === null ? null : $count === 1,
            'odd' => false,
            'even' => true,
            'depth' => count($this->loops) + 1,
            'parent' => $parent === null ? null : clone $parent,
        ];
    }

    /** Advance the active loop and publish its current metadata as `$loop`. */
    public function incrementLoopIndices(): void
    {
        $index = array_key_last($this->loops);
        if ($index === null) {
            throw new \LogicException('Cannot advance a view loop when no loop is active.');
        }

        $loop = $this->loops[$index];
        $iteration = $loop['iteration'] + 1;
        $this->loops[$index] = array_merge($loop, [
            'iteration' => $iteration,
            'index' => $iteration - 1,
            'remaining' => $loop['count'] === null ? null : $loop['count'] - $iteration,
            'first' => $iteration === 1,
            'last' => $loop['count'] === null ? null : $iteration === $loop['count'],
            'odd' => ($iteration % 2) === 1,
            'even' => ($iteration % 2) === 0,
        ]);
    }

    /** End the active loop and restore its parent as the current `$loop`. */
    public function popLoop(): void
    {
        if ($this->loops === []) {
            throw new \LogicException('Cannot end a view loop when no loop is active.');
        }

        array_pop($this->loops);
    }

    /** Return current loop metadata, or null outside a loop. */
    public function getLastLoop(): ?object
    {
        $index = array_key_last($this->loops);
        if ($index === null) {
            return null;
        }

        $loop = $this->loops[$index];

        return (object) $loop;
    }

    public function set_echo_format(string $format): void
    {
        $this->echo_format = $format;
    }

    /**
     * Dump-autoload map: view name => compiled absolute path. Fast include skips locate.
     *
     * @param $map array<string, string>
     *
     * @return void
     */
    public function set_compiled_map(array $map): void
    {
        $this->compiled_map = $map;
        $this->compiled_files = [];
        $this->fresh_views = [];
    }

    /**
     * @param $view string
     *
     * @return string
     */
    public function compiled_path(string $view): string
    {
        return $this->compiled_file($view);
    }

    public function template_file(string $template_name): string
    {
        return $this->templates->find($template_name);
    }

    /**
     * @return Directives
     */
    public function directives(): Directives
    {
        return $this->directives;
    }

    /** Invalidate cached PHP templates after runtime directives are registered. */
    public function invalidateCompiledTemplates(): void
    {
        foreach ($this->compiled_files as $compiled) {
            if (is_file($compiled)) {
                @unlink($compiled);
                if (function_exists('opcache_invalidate')) { @opcache_invalidate($compiled, true); }
            }
        }
        $this->compiled_files = [];
        $this->fresh_views = [];
        $this->directive_revision = $this->directives->revision();
    }

    /**
     * @param $name string
     * @param $args mixed ...
     *
     * @return bool
     */
    public function check(string $name, mixed ...$args): bool
    {
        return $this->directives->check($name, ...$args);
    }

    /**
     * @param $view string
     *
     * @return void
     */
    public function compile(string $view): void
    {
        $this->compile_view($view, true);
    }

    /**
     * @return Compiler
     */
    public function compiler(): Compiler
    {
        return $this->compiler ??= new Compiler($this->directives, $this->echo_format);
    }

    private function include_view(string $view): void
    {
        static $profile_request = null;
        $profile_request ??= (\getenv('WEBKERNEL_PROFILE_REQUEST') ?: '') === '1';
        $phase_started_at = $profile_request ? \hrtime(true) : 0;
        $view = $this->qualify_view($view);
        $this->ensure_compiled($view);
        $compiled = $this->compiled_file($view);
        if ($compiled === '' || ! is_file($compiled)) {
            unset($this->compiled_files[$view]);
            $this->compile_view($view, true);
            $compiled = $this->compiled_file($view);
        }
        if ($profile_request) {
            $GLOBALS['webkernel_request_phase_timings']['view_cache_check'] = ($GLOBALS['webkernel_request_phase_timings']['view_cache_check'] ?? 0.0) + ((\hrtime(true) - $phase_started_at) / 1e6);
            $phase_started_at = \hrtime(true);
        }
        if ($compiled === '' || ! is_file($compiled)) {
            $caller = $this->caller_location();
            $ref = $caller !== '' ? ' (referenced in '.$caller.')' : '';
            throw new \RuntimeException($this->templates->explain($view).$ref);
        }
        $template = $this->templates->find($view);
        if ($profile_request) {
            $GLOBALS['webkernel_request_phase_timings']['view_template_lookup'] = ($GLOBALS['webkernel_request_phase_timings']['view_template_lookup'] ?? 0.0) + ((\hrtime(true) - $phase_started_at) / 1e6);
            $phase_started_at = \hrtime(true);
        }
        $this->render_stack[] = ['view' => $view, 'template' => $template];
        try {
            if (\function_exists('webkernel_profile_enter')) {
                \webkernel_profile_enter($compiled);
                try {
                    $this->evaluate($compiled, $this->variables);
                } finally {
                    \webkernel_profile_leave();
                    if ($profile_request) {
                        $GLOBALS['webkernel_request_phase_timings']['view_evaluate'] = ($GLOBALS['webkernel_request_phase_timings']['view_evaluate'] ?? 0.0) + ((\hrtime(true) - $phase_started_at) / 1e6);
                    }
                }

                return;
            }
            $this->evaluate($compiled, $this->variables);
            if ($profile_request) {
                $GLOBALS['webkernel_request_phase_timings']['view_evaluate'] = ($GLOBALS['webkernel_request_phase_timings']['view_evaluate'] ?? 0.0) + ((\hrtime(true) - $phase_started_at) / 1e6);
            }
        } finally {
            array_pop($this->render_stack);
        }
    }

    /** Keeps engine paths and render-stack state outside the template's variable scope. */
    private function evaluate(string $__webkernel_file, array $__webkernel_data): void
    {
        $__webkernel_component = $__webkernel_data['__webkernel_component'] ?? $this->view_component ?? null;
        if ($__webkernel_component === $this) {
            $__webkernel_component = null;
        }

        $__webkernel_previous_component = $this->view_component;
        $this->view_component = $__webkernel_component;

        $profile_views = (getenv('WEBKERNEL_PROFILE_VIEWS') ?: '') === '1';
        $profile_started_at = $profile_views ? hrtime(true) : 0;
        if ($profile_views) {
            $render = $this->render_stack[array_key_last($this->render_stack)] ?? null;
            $this->profile_evaluation_stack[] = [
                'view' => $render['view'] ?? 'unknown',
                'started_at' => $profile_started_at,
                'child_ms' => 0.0,
            ];
        }

        try {
            $this->include_compiled(
                $__webkernel_file,
                $__webkernel_component === null
                    ? $__webkernel_data
                    : ['__webkernel_component' => $__webkernel_component] + $__webkernel_data,
            );
        } finally {
            $this->view_component = $__webkernel_previous_component;
            if ($profile_views) {
                $elapsed_ms = (hrtime(true) - $profile_started_at) / 1e6;
                $frame = array_pop($this->profile_evaluation_stack);
                if (is_array($frame)) {
                    $exclusive_ms = max(0.0, $elapsed_ms - $frame['child_ms']);
                    $metrics = $GLOBALS['webkernel_view_exclusive_metrics'] ?? [];
                    $metrics[$frame['view']] = ($metrics[$frame['view']] ?? 0.0) + $exclusive_ms;
                    $GLOBALS['webkernel_view_exclusive_metrics'] = $metrics;
                    $parent_index = array_key_last($this->profile_evaluation_stack);
                    if ($parent_index !== null) {
                        $this->profile_evaluation_stack[$parent_index]['child_ms'] += $elapsed_ms;
                    }
                }
            }
        }
    }

    /**
     * Includes a compiled template with the rendering component's class scope.
     *
     * Livewire binds a component view to the component, which is what lets
     * Filament templates read protected members such as `$this->hasActionsModalRendered`.
     * The engine keeps `$this` (its generated calls need it) while the closure's
     * scope is the component, so those protected accesses stay valid.
     *
     * @param array<string, mixed> $__webkernel_data
     */
    private function include_compiled(string $__webkernel_file, array $__webkernel_data): void
    {
        $__webkernel_render_component = $__webkernel_data['__webkernel_component'] ?? null;

        $__webkernel_render = function (string $__webkernel_file, array $__webkernel_data) use ($__webkernel_render_component): void {
            $__webkernel_component = $__webkernel_render_component ?? $this;
            $__webkernel_engine = $this;

            extract($__webkernel_data, EXTR_SKIP);

            include $__webkernel_file;
        };

        if (! is_object($__webkernel_render_component) || $__webkernel_render_component === $this) {
            $__webkernel_render($__webkernel_file, $__webkernel_data);

            return;
        }

        \Closure::bind($__webkernel_render, $this, $__webkernel_render_component::class)($__webkernel_file, $__webkernel_data);
    }

    private function ensure_compiled(string $view): void
    {
        if ($this->directive_revision !== $this->directives->revision()) {
            $this->invalidateCompiledTemplates();
        }
        if ($this->mode === Mode::Fast) {
            return;
        }
        if ($this->mode === Mode::Slow) {
            $this->compile_view($view, false);

            return;
        }
        if (! $this->is_fresh_view($view)) {
            $this->compile_view($view, true);
        }
    }

    private function is_fresh_view(string $view): bool
    {
        if (array_key_exists($view, $this->fresh_views)) {
            return $this->fresh_views[$view];
        }

        return $this->fresh_views[$view] = ! $this->expired($view);
    }

    /**
     * @param $view string
     * @param $forced bool
     *
     * @return void
     */
    private function compile_view(string $view, bool $forced): void
    {
        if ($this->directive_revision !== $this->directives->revision()) {
            $this->compiled_files = [];
            $this->fresh_views = [];
            $this->directive_revision = $this->directives->revision();
        }
        if (! $forced && $this->mode !== Mode::Slow && ! $this->expired($view)) {
            return;
        }
        $template = $this->template_file($view);
        $compiled = $this->compiled_file($view);
        if ($template === '' || $compiled === '') {
            $caller = $this->caller_location();
            $ref = $caller !== '' ? ' (referenced in '.$caller.')' : '';
            throw new \RuntimeException($this->templates->explain($view).$ref);
        }
        $compiler = $this->compiler();
        $module = null;
        if (str_contains($view, '::')) {
            $module = explode('::', $view, 2)[0];
            if ($module === 'webkernel') {
                $module = 'platform';
            }
        }
        $compiler->set_acl_module($module);
        $compiler->compile_file($template, $compiled);
        unset($this->compiled_files[$view]);
        $this->fresh_views[$view] = true;
    }

    /**
     * Finds caller file and line for error messages.
     *
     * @return string
     */
    private function caller_location(): string
    {
        $parent = end($this->render_stack);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $caller_file = '';
        $caller_line = 0;

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            if (
                $file === '' ||
                str_ends_with($file, '/Engine.php') ||
                str_ends_with($file, '/View.php') ||
                str_ends_with($file, '/Compiler.php') ||
                str_ends_with($file, '/Directives.php')
            ) {
                continue;
            }
            $caller_file = $file;
            $caller_line = (int) ($frame['line'] ?? 0);
            break;
        }

        if ($parent !== false && $parent['view'] !== '') {
            $view_name = $parent['view'];
            $template = $parent['template'] !== '' ? $parent['template'] : $caller_file;
            $loc = $template !== '' ? ($caller_line > 0 ? $template.':'.$caller_line : $template) : '';

            return $loc !== '' ? "view '{$view_name}' at {$loc}" : "view '{$view_name}'";
        }

        if ($caller_file !== '') {
            return $caller_line > 0 ? $caller_file.':'.$caller_line : $caller_file;
        }

        return '';
    }

    private function expired(string $view): bool
    {
        $template = $this->template_file($view);
        $compiled = $this->compiled_file($view);
        if ($template === '' || $compiled === '' || ! is_file($compiled)) {
            return true;
        }

        $template_modified_at = filemtime($template);
        $compiled_modified_at = filemtime($compiled);
        if (! is_int($template_modified_at) || ! is_int($compiled_modified_at)) {
            return true;
        }

        return $compiled_modified_at < max($template_modified_at, Compiler::lastModifiedAt());
    }

    private function compiled_file(string $template_name): string
    {
        if (isset($this->compiled_files[$template_name])) {
            return $this->compiled_files[$template_name];
        }
        $mapped = $this->compiled_map_path($template_name);
        if ($mapped !== '') {
            return $this->compiled_files[$template_name] = $mapped;
        }
        $full = $this->template_file($template_name);
        if ($full === '') {
            return $this->compiled_files[$template_name] = '';
        }

        $source_hash = hash_file('sha256', $full);
        $cache_key = sha1($full."\0".(is_string($source_hash) ? $source_hash : ''));

        return $this->compiled_files[$template_name] = $this->compile_dpath.'/'.basename($template_name).'_'.$cache_key.'.view.php.compiled';
    }

    /**
     * `foo/index.view.php` is dumped as `ns::foo` and requested as `ns::foo.index`.
     *
     * @param $template_name string
     *
     * @return string
     */
    private function compiled_map_path(string $template_name): string
    {
        if (isset($this->compiled_map[$template_name]) && $this->compiled_map[$template_name] !== '') {
            return $this->compiled_map[$template_name];
        }
        if (\str_ends_with($template_name, '.index')) {
            $short = \substr($template_name, 0, -6);

            return (isset($this->compiled_map[$short]) && $this->compiled_map[$short] !== '')
                ? $this->compiled_map[$short]
                : '';
        }
        // Component files use the `name/name.view.php` convention.
        $separator = strpos($template_name, '::');
        if ($separator !== false) {
            $name = substr($template_name, $separator + 2);
            $component = $template_name.'.'.basename(str_replace('.', '/', $name));
            if (isset($this->compiled_map[$component]) && $this->compiled_map[$component] !== '') {
                return $this->compiled_map[$component];
            }
        }
        $index = $template_name.'.index';

        return (isset($this->compiled_map[$index]) && $this->compiled_map[$index] !== '')
            ? $this->compiled_map[$index]
            : '';
    }

    /** Resolve unqualified child views and components in the calling view namespace. */
    private function qualify_view(string $view): string
    {
        if (str_contains($view, '::')) {
            return $view;
        }

        $namespace = $this->current_namespace();

        return $namespace === '' ? $view : $namespace.'::'.$view;
    }

    private function current_namespace(): string
    {
        if ($this->namespace_overrides !== []) {
            return $this->namespace_overrides[array_key_last($this->namespace_overrides)];
        }
        $parent = end($this->render_stack);
        $parent_name = is_array($parent) ? $parent['view'] : '';
        $separator = strpos($parent_name, '::');

        return $separator === false ? '' : substr($parent_name, 0, $separator);
    }

    private function extend_section(string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $content = str_replace(self::PARENT_KEY, $content, $this->sections[$section]);
        }
        $this->sections[$section] = $content;
    }

    private function extend_push(string $section, string $content): void
    {
        $this->pushes[$section][$this->render_count] = ($this->pushes[$section][$this->render_count] ?? '').$content;
    }

    private function yield_push_content(string $section, mixed $default = ''): string
    {
        if ($section === '') {
            return is_string($default) ? $default : '';
        }
        if (! isset($this->pushes[$section])) {
            return is_string($default) ? $default : '';
        }

        return implode(array_reverse($this->pushes[$section]));
    }

}
