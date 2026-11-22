<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Support;

/** Resolves a logical view name against explicitly registered view roots. */
final class TemplateLocator
{
    /** @var array<string, list<string>> */
    private array $directories = [];

    /** @var array<string, string> */
    private array $resolved = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @param list<string> $default_directories */
    public function __construct(array $default_directories = [])
    {
        $this->directories[''] = array_values(array_map(static fn (string $path): string => rtrim($path, '/\\'), $default_directories));
    }

    public function add(string $namespace, string $path): void
    {
        $this->directories[$namespace][] = rtrim($path, '/\\');
        $this->resolved = [];
    }

    public function alias(string $name, string $file): void
    {
        if (! str_contains($name, '::') || ! str_ends_with($file, '.view.php') || ! is_file($file)) {
            throw new \InvalidArgumentException('Component aliases require a namespaced name and an existing view file.');
        }
        $this->aliases[$name] = $file;
        unset($this->resolved[$name]);
    }

    public function find(string $template): string
    {
        if (isset($this->aliases[$template])) { return $this->aliases[$template]; }
        if (isset($this->resolved[$template])) { return $this->resolved[$template]; }
        [$namespace, $name] = $this->split($template);
        $relative = str_contains($name, '/') ? [$name] : [str_replace('.', '/', $name).'.view.php', str_replace('.', '/', $name).'/index.view.php'];
        foreach ($relative as $candidate) {
            foreach ($this->directories($namespace) as $directory) {
                $path = $directory.'/'.$candidate;
                if (is_file($path)) { return $this->resolved[$template] = $path; }
            }
        }
        return $this->resolved[$template] = '';
    }

    public function explain(string $template): string
    {
        [$namespace, $name] = $this->split($template);
        $relative = str_contains($name, '/') ? [$name] : [str_replace('.', '/', $name).'.view.php', str_replace('.', '/', $name).'/index.view.php'];
        $paths = [];
        foreach ($relative as $candidate) {
            foreach ($this->directories($namespace) as $directory) { $paths[] = $directory.'/'.$candidate; }
        }
        return "Unknown view: '{$template}'. Paths tried: ".($paths === [] ? '(no directories declared for namespace '.($namespace !== '' ? $namespace : '(default)').')' : implode(', ', $paths));
    }

    /** @return list<string> */
    private function directories(string $namespace): array
    {
        return $this->directories[$namespace] ?? [];
    }

    /** @return array{string, string} */
    private function split(string $template): array
    {
        $separator = strpos($template, '::');
        return $separator === false ? ['', $template] : [substr($template, 0, $separator), substr($template, $separator + 2)];
    }
}
