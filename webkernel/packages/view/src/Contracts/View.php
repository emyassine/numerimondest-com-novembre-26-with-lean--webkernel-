<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Contracts;

use Webkernel\View\Contracts\Renderable;
use Webkernel\View\Contracts\View;

interface View extends Renderable
{
    public function name(): string;
    public function with(string|array $key, mixed $value = null): View;
    public function getData(): array;
}
