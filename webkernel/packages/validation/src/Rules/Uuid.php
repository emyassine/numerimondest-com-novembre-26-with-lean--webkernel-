<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class Uuid implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * @var int<0, 8>|'nil'|'max'|null
     */
    protected int|string|null $version = null;

    /**
     * Specify the UUID version.
     *
     * @param  int<0, 8>|'nil'|'max'  $version
     */
    public function version(int|string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return $this->version === null ? ['uuid'] : ['uuid', $this->version];
    }
}
