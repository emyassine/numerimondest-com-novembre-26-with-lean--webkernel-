<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class InArrayKeys implements ParameterizedRule
{
    use FormatsParameters;

    protected array $keys;

    /**
     * Create a new in_array_keys rule instance.
     */
    public function __construct(array|string $keys)
    {
        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['in_array_keys', ...array_values($this->keys)];
    }
}
