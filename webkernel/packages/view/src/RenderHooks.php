<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/** Component-scoped fragments evaluated only at an enabled render outlet. */
final class RenderHooks
{
    /** @var array<string, array<string, array{Closure, array<string, mixed>}>> */
    private array $definitions = [];

    /** @var array<string, true>|null */
    private ?array $included = null;

    /** @var array<string, true> */
    private array $excluded = [];

    /** @var array<string, string> */
    private array $variants = [];

    /** @var array<string, string> */
    private array $rendered = [];

    /** @var array<string, true> */
    private array $rendering = [];

    /**
     * @param $id string
     * @param $content Closure(array<string, mixed>): void
     * @param $scope array<string, mixed>
     * @param $variant string
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function define(string $id, Closure $content, array $scope, string $variant = 'default'): void
    {
        if ($id === '' || $variant === '' || isset($this->definitions[$id][$variant])) {
            throw new InvalidArgumentException('Render hook IDs and variants must be nonempty and unique: '.$id.'/'.$variant);
        }
        $this->definitions[$id][$variant] = [$content, $scope];
    }

    /**
     * @param $include string|list<string>|null
     * @param $exclude string|list<string>|null
     * @param $variants array<string, string>
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function select(string|array|null $include = null, string|array|null $exclude = null, array $variants = []): void
    {
        if ($include !== null && $exclude !== null) {
            throw new InvalidArgumentException('Render hook include and exclude cannot be combined.');
        }
        foreach ($variants as $id => $variant) {
            if (! is_string($variant) || ! isset($this->definitions[$id][$variant])) {
                throw new InvalidArgumentException('Undefined render hook variant for: '.$id);
            }
        }
        $this->variants = $variants;
        $this->included = $include === null ? null : $this->ids($include);
        $this->excluded = $exclude === null ? [] : $this->ids($exclude);
    }

    /**
     * @param $id string
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id][$this->variants[$id] ?? 'default'])
            && ($this->included === null || isset($this->included[$id]))
            && ! isset($this->excluded[$id]);
    }

    /**
     * @param $id string
     *
     * @return string
     * @throws RuntimeException
     * @throws Throwable
     */
    public function render(string $id): string
    {
        if (! $this->has($id)) {
            return '';
        }
        $variant = $this->variants[$id] ?? 'default';
        $key = $id."\0".$variant;
        if (isset($this->rendered[$key])) {
            return $this->rendered[$key];
        }
        if (isset($this->rendering[$key])) {
            throw new RuntimeException('Recursive render hook: '.$id);
        }
        $this->rendering[$key] = true;
        [$content, $scope] = $this->definitions[$id][$variant];
        $scope['renderhooks'] = $this;
        $buffer_level = ob_get_level();
        ob_start();
        try {
            $content($scope);

            return $this->rendered[$key] = trim((string) ob_get_clean());
        } finally {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }
            unset($this->rendering[$key]);
        }
    }

    /**
     * @param $ids string|list<string>
     *
     * @return array<string, true>
     * @throws InvalidArgumentException
     */
    private function ids(string|array $ids): array
    {
        $selected = [];
        foreach (is_string($ids) ? explode(',', $ids) : $ids as $id) {
            if (! is_string($id)) {
                throw new InvalidArgumentException('Render hook IDs must be strings.');
            }
            $id = trim($id);
            if ($id === '') {
                continue;
            }
            if (! isset($this->definitions[$id])) {
                throw new InvalidArgumentException('Undefined render hook: '.$id);
            }
            $selected[$id] = true;
        }

        return $selected;
    }
}
