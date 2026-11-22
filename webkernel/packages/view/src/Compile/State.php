<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Compile;

/**
 * Per-compile bag. One instance per compile_string() call.
 */
final class State
{
    /** @var list<string> */
    public array $footer = [];

    public int $forelse = 0;

    public int $loop_id = 0;

    /** @var list<int> */
    public array $loop_stack = [];

    public int $extend_id = 0;

    /** @var list<string> */
    public array $capture_stack = [];

    public int $capture_id = 0;

    /** @var array<string, list<string>> */
    private array $directive_blocks = [];

    /** @var array<string, int> */
    private array $directive_counts = [];

    /**
     * @param $echo_format string
     * @param $directives Directives
     * @param $acl_module string|null
     */
    public function __construct(
        public readonly string $echo_format,
        public readonly Directives $directives,
        public readonly ?string $acl_module = null,
        public readonly ?string $source_path = null,
    ) {
    }

    public function beginDirectiveBlock(string $name): string
    {
        $index = $this->directive_counts[$name] ?? 0;
        $this->directive_counts[$name] = $index + 1;
        $key = hash('sha256', ($this->source_path ?? 'inline')."\0".$name."\0".$index);
        $this->directive_blocks[$name][] = $key;

        return $key;
    }

    public function endDirectiveBlock(string $name): string
    {
        if (($this->directive_blocks[$name] ?? []) === []) {
            throw new \InvalidArgumentException(sprintf('@end%s has no matching block.', $name));
        }
        $key = array_pop($this->directive_blocks[$name]);
        if (! is_string($key)) {
            throw new \InvalidArgumentException(sprintf('@end%s has no matching block.', $name));
        }

        return $key;
    }
}
