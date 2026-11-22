<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Concerns;

use Webkernel\View\AttributeBag;

trait HasAttributes
{
    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @param array<string, mixed> $attributes */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /** @param $classes string|array<int|string, mixed> */
    public function classes(string|array $classes): static
    {
        $this->attributes = $this->attributeBag()->class($classes)->to_array();

        return $this;
    }

    protected function attributeBag(): AttributeBag
    {
        return new AttributeBag($this->attributes);
    }
}
