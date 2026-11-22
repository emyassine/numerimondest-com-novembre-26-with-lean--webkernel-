<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class Distinct implements ParameterizedRule
{
    use FormatsParameters;

    protected array $options = [];

    /**
     * Compare values using strict comparisons.
     */
    public function strict(): static
    {
        $this->options[] = 'strict';

        return $this;
    }

    /**
     * Ignore case when comparing string values.
     */
    public function ignoreCase(): static
    {
        $this->options[] = 'ignore_case';

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['distinct', ...array_values(array_unique($this->options))];
    }
}
