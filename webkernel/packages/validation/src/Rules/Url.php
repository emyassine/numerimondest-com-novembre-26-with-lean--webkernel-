<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class Url implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * @var string[]|null
     */
    protected ?array $protocols = null;

    /**
     * Specify the allowed URL protocols.
     *
     * @param  string[]|null  $protocols
     */
    public function protocols(array $protocols): static
    {
        $this->protocols = $protocols;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['url', ...array_values($this->protocols ?? [])];
    }
}
