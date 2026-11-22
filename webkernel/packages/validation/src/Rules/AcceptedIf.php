<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class AcceptedIf implements ParameterizedRule
{
    use FormatsParameters;

    protected string $anotherField;

    /**
     * @var array<int, string|int|float|bool|null>
     */
    protected array $values;

    /**
     * Create a new accepted_if rule instance.
     */
    public function __construct(string $anotherField, string|int|float|bool|null|array $values)
    {
        $this->anotherField = $anotherField;
        $this->values = is_array($values) ? $values : array_slice(func_get_args(), 1);
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        $values = array_map(
            static fn ($value) => is_string($value) ? $value : json_encode($value),
            $this->values,
        );

        return ['accepted_if', $this->anotherField, ...array_values($values)];
    }
}
