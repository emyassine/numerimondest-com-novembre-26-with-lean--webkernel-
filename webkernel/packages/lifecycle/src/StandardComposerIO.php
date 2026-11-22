<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle;

use Composer\IO\IOInterface;
use LogicException;

/**
 * Concise output API scoped to the active Composer lifecycle callback.
 */
final class StandardComposerIO
{
    private static ?IOInterface $io = null;

    /** @var list<array{type: 'branch'|'line'|'blank', message: string, children: list<array>, continue_branch: bool}> */
    private static array $tree = [];

    /** @var list<int> */
    private static array $tree_path = [];

    private static bool $tree_open = false;

    private static bool $banner_written = false;

    /** Binds the Composer IO used by the active lifecycle event. */
    public static function bind(IOInterface $io): ?IOInterface
    {
        $previous_io = self::$io;
        self::$io    = $io;

        return $previous_io;
    }

    /** Restores the Composer IO context that was active before bind(). */
    public static function restore(?IOInterface $io): void
    {
        self::$io = $io;
    }

    /**
     * Renders a lifecycle event heading and its collected output tree.
     *
     * @param $event_name string Composer event name.
     * @param $draw_children callable(): mixed Callback that collects event output.
     * @param $completion callable(): string|null Optional final summary callback.
     * @return mixed The callback result.
     * @throws LogicException When output trees are nested.
     * @throws \Throwable If an output callback fails.
     */
    public static function tree(string $event_name, callable $draw_children, ?callable $completion = null): mixed
    {
        if (self::$tree_open) {
            throw new LogicException('Lifecycle output trees cannot be nested.');
        }

        self::$tree = [];
        self::$tree_path = [];
        self::$tree_open = true;

        self::io()->write(sprintf(
            "\n<fg=gray>•> </><fg=cyan>Webkernel Lifecycle</> <fg=gray>· %s</>",
            $event_name,
        ));
        if (! self::$banner_written) {
            $year = date('Y');
            self::io()->write([
                '<fg=gray>│</>',
                '<fg=gray>│  This Tool is part of Webkernel.</>',
                sprintf('<fg=gray>│  (c) 2025 - %s Numerimondes, El Moumen Yassine</>', $year),
                '<fg=gray>│  Yassine El Moumen <https://www.linkedin.com/in/elmoumenyassine/></>',
                '<fg=gray>│</>',
            ]);
            self::$banner_written = true;
        } else {
            self::io()->write('<fg=gray>│</>');
        }

        $completed = false;
        try {
            $result = $draw_children();
            $completed = true;
            return $result;
        } finally {
            self::renderNodes(self::$tree, '');
            self::$tree = [];
            self::$tree_path = [];
            self::$tree_open = false;
            if ($completed && $completion !== null) {
                self::io()->write(sprintf(
                    "\n<fg=gray>•> </><fg=cyan>Webkernel Lifecycle</> %s",
                    $completion(),
                ));
            }
            self::io()->write('');
        }
    }

    /**
     * Collects output under a named tree branch.
     *
     * @param $message string Branch label.
     * @param $draw_children callable(): mixed Callback that collects branch output.
     * @param $color string Optional Symfony console color name.
     * @return mixed The callback result.
     * @throws \Throwable If the branch callback fails.
     */
    public static function branch(string $message, callable $draw_children, string $color = 'default'): mixed
    {
        $styled_message = $color === 'default'
            ? $message
            : sprintf('<fg=%s>%s</>', $color, $message);
        $index = self::appendNode('branch', $styled_message);
        self::$tree_path[] = $index;

        try {
            return $draw_children();
        } finally {
            array_pop(self::$tree_path);
        }
    }

    /** Adds a line to the active output tree. */
    public static function line(string $message): void
    {
        self::appendNode('line', $message);
    }

    /** Adds a status line and optional elapsed time to the active output tree. */
    public static function result(string $status, string $message, ?string $duration = null): void
    {
        $style = match ($status) {
            'PASS', 'DONE' => 'green',
            'WARN' => 'yellow',
            'FAIL', 'BLOCKED' => 'red',
            'CHECK', 'RUN', 'START' => 'cyan',
            default => 'gray',
        };
        $text = sprintf('<fg=%s>[%s]</> %s', $style, $status, $message);

        if ($duration !== null) {
            $text .= sprintf(' <fg=gray>%s</>', $duration);
        }

        self::line($text);
    }

    /** Adds a blank line to the active output tree. */
    public static function blankLine(bool $continue_current_branch = false): void
    {
        self::appendNode('blank', '', $continue_current_branch);
    }

    /**
     * Formats elapsed nanoseconds using a readable time unit.
     * @throws LogicException When a negative duration is provided.
     */
    public static function duration(int $nanoseconds): string
    {
        if ($nanoseconds < 0) {
            throw new LogicException('Duration cannot be negative.');
        }

        if ($nanoseconds < 1_000_000) {
            return sprintf('%.0f µs', $nanoseconds / 1_000);
        }

        if ($nanoseconds < 1_000_000_000) {
            return sprintf('%.2f ms', $nanoseconds / 1_000_000);
        }

        if ($nanoseconds < 60_000_000_000) {
            return sprintf('%.2f s', $nanoseconds / 1_000_000_000);
        }

        $minutes = intdiv($nanoseconds, 60_000_000_000);
        $seconds = ($nanoseconds % 60_000_000_000) / 1_000_000_000;

        return sprintf('%d min %.1f s', $minutes, $seconds);
    }

    /**
     * Writes one or more informational messages through the active Composer IO.
     *
     * @param $messages string|list<string> Message or message lines.
     * @throws LogicException When no Composer IO is bound.
     */
    public static function write(string|array $messages): void
    {
        if (! self::$tree_open) {
            self::io()->write($messages);
            return;
        }

        foreach ((array) $messages as $message) {
            foreach (preg_split('/\R/', (string) $message) ?: [''] as $line) {
                self::line($line);
            }
        }
    }

    /**
     * Writes one or more error messages through the active Composer IO.
     *
     * @param $messages string|list<string> Message or message lines.
     * @throws LogicException When no Composer IO is bound.
     */
    public static function error(string|array $messages): void
    {
        if (! self::$tree_open) {
            self::io()->writeError($messages);
            return;
        }

        foreach ((array) $messages as $message) {
            foreach (preg_split('/\R/', (string) $message) ?: [''] as $line) {
                self::line(sprintf('<fg=red>%s</>', $line));
            }
        }
    }

    /** Appends a typed node to the current output tree branch. */
    private static function appendNode(string $type, string $message, bool $continue_branch = false): int
    {
        $children = &self::currentChildren();
        $children[] = [
            'type' => $type,
            'message' => $message,
            'children' => [],
            'continue_branch' => $continue_branch,
        ];

        return (int) array_key_last($children);
    }

    /**
     * Returns the child list for the currently active tree branch.
     *
     * @return list<array{type: 'branch'|'line'|'blank', message: string, children: list<array>, continue_branch: bool}>
     */
    private static function &currentChildren(): array
    {
        $children = &self::$tree;

        foreach (self::$tree_path as $index) {
            $children = &$children[$index]['children'];
        }

        return $children;
    }

    /**
     * Renders tree nodes with connectors for their nesting level.
     *
     * @param $nodes list<array{type: 'branch'|'line'|'blank', message: string, children: list<array>, continue_branch: bool}>
     */
    private static function renderNodes(array $nodes, string $prefix): void
    {
        $visible_indexes = [];
        foreach ($nodes as $index => $node) {
            if ($node['type'] !== 'blank') {
                $visible_indexes[] = $index;
            }
        }
        $last_index = $visible_indexes === [] ? null : $visible_indexes[array_key_last($visible_indexes)];

        foreach ($nodes as $index => $node) {
            if ($node['type'] === 'blank') {
                $blank_prefix = $prefix . ($node['continue_branch'] ? '│' : '');
                $blank_prefix = preg_replace('/ +$/', '', $blank_prefix) ?? $blank_prefix;
                self::io()->write(sprintf('<fg=gray>%s</>', $blank_prefix));
                continue;
            }

            $is_last = $index === $last_index;
            $connector = $is_last ? '└── ' : '├── ';
            self::io()->write(sprintf('<fg=gray>%s%s</>', $prefix, $connector) . $node['message']);

            if ($node['children'] !== []) {
                self::renderNodes($node['children'], $prefix . ($is_last ? '    ' : '│   '));
            }
        }
    }

    /** Returns the Composer IO bound to the active event. @throws LogicException If no IO is bound. */
    private static function io(): IOInterface
    {
        if (self::$io === null) {
            throw new LogicException('StandardComposerIO can only be used during a Composer lifecycle callback.');
        }

        return self::$io;
    }

    /** Prevents instantiation of this static utility. */
    private function __construct() {}
}
