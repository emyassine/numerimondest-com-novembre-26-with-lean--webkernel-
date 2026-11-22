<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Action;

use Webkernel\View\View;
use Webkernel\View\ViewResult;

/** Resolves a multipage subroute to the same server-rendered view. */
final readonly class MultipageAction
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $view,
        public string $base,
        public array $data = [],
    ) {}

    public function __invoke(string $multipage_current): ViewResult
    {
        return View::make($this->view, array_merge($this->data, [
            'multipage_current' => $multipage_current,
            'multipage_base' => $this->base,
        ]));
    }

    /** @param array{view: string, base: string, data: array<string, mixed>} $properties */
    public static function __set_state(array $properties): self
    {
        return new self($properties['view'], $properties['base'], $properties['data']);
    }
}
