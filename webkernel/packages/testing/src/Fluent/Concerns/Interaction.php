<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Testing\Fluent\Concerns;

use \Webkernel\Utilities\Str;
use PHPUnit\Framework\Assert as PHPUnit;

trait Interaction
{
    /**
     * The list of interacted properties.
     *
     * @var array
     */
    protected $interacted = [];

    /**
     * Marks the property as interacted.
     *
     * @param  string  $key
     * @return void
     */
    protected function interactsWith(string $key): void
    {
        $prop = Str::before($key, '.');

        if (! in_array($prop, $this->interacted, true)) {
            $this->interacted[] = $prop;
        }
    }

    /**
     * Asserts that all properties have been interacted with.
     *
     * @return void
     */
    public function interacted(): void
    {
        PHPUnit::assertSame(
            [],
            array_diff(array_keys($this->prop()), $this->interacted),
            $this->path
                ? sprintf('Unexpected properties were found in scope [%s].', $this->path)
                : 'Unexpected properties were found on the root level.'
        );
    }

    /**
     * Disables the interaction check.
     *
     * @return $this
     */
    public function etc(): static
    {
        $this->interacted = array_keys($this->prop());

        return $this;
    }

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    abstract protected function prop(?string $key = null);
}
