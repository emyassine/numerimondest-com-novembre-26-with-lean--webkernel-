<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Compile;

/**
 * Custom compile handlers and runtime conditions (`View::if()`).
 */
final class Directives
{
    /** @var array<string, callable(string, State): string> */
    private array $handlers = [];

    private int $revision = 0;

    /** @var array<string, callable(mixed...): bool> */
    private array $conditions = [];

    /**
     * @param $name string
     * @param $handler callable(string, State): string
     *
     * @return void
     */
    public function handler(string $name, callable $handler): void
    {
        $this->handlers[$name] = $handler;
        $this->revision++;
    }

    /**
     * @param $name string
     *
     * @return callable(string, State): string|null
     */
    public function get(string $name): ?callable
    {
        return $this->handlers[$name] ?? null;
    }

    public function revision(): int
    {
        return $this->revision;
    }

    /** @return list<string> */
    public function conditionNames(): array
    {
        return array_keys($this->conditions);
    }

    /** @return array<string, callable> */
    public function all(): array
    {
        return $this->handlers;
    }

    /** @param array<string, callable> $handlers */
    public function replace(array $handlers): void
    {
        foreach ($handlers as $name => $handler) {
            if (! is_string($name) || ! is_callable($handler)) {
                throw new \InvalidArgumentException('View directives must map names to callable handlers.');
            }
        }
        $this->handlers = $handlers;
        $this->revision++;
    }

    /**
     * @param $name string
     * @param $callback callable(mixed...): bool
     *
     * @return void
     */
    public function condition(string $name, callable $callback): void
    {
        $this->conditions[$name] = $callback;
        $this->handler($name, static function (string $expression) use ($name): string {
            $tmp = Php::strip_parentheses($expression);

            return $tmp !== ''
                ? Php::OPEN."if (\$this->check('$name', $tmp)): ?>"
                : Php::OPEN."if (\$this->check('$name')): ?>";
        });
        $this->handler('else'.$name, static function (string $expression) use ($name): string {
            $tmp = Php::strip_parentheses($expression);

            return $tmp !== ''
                ? Php::OPEN."elseif (\$this->check('$name', $tmp)): ?>"
                : Php::OPEN."elseif (\$this->check('$name')): ?>";
        });
        $this->handler('end'.$name, static fn (string $_): string => Php::OPEN.'endif; ?>');
        $this->handler('unless'.$name, static function (string $expression) use ($name): string {
            $tmp = Php::strip_parentheses($expression);

            return $tmp !== ''
                ? Php::OPEN."if (! \$this->check('$name', $tmp)): ?>"
                : Php::OPEN."if (! \$this->check('$name')): ?>";
        });
        $this->handler('endunless'.$name, static fn (string $_): string => Php::OPEN.'endif; ?>');
    }

    /**
     * @param $name string
     * @param $args mixed ...
     *
     * @return bool
     */
    public function check(string $name, mixed ...$args): bool
    {
        $callback = $this->conditions[$name] ?? null;
        if ($callback === null) {
            throw new \RuntimeException('Unknown view condition ['.$name.'].');
        }

        return (bool) $callback(...$args);
    }
}
