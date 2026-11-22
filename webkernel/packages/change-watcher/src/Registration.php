<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

use Closure;

/** @internal Immutable registration identity also detects replacement during a build. */
final readonly class Registration
{
    /**
     * Records a consumer's sources and compilation callback.
     *
     * @param $sources list<string> Absolute file or directory paths, in consumer order.
     * @param $compiler Closure(list<string>): string|null Optional artifact compiler.
     * @param $revision string Consumer version identifying compiler behavior.
     */
    public function __construct(
        public array $sources,
        public ?Closure $compiler,
        public string $revision,
    ) {
    }
}
