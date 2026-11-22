<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\ParameterizedRule;
use \Webkernel\Validation\Rules\Concerns\FormatsParameters;

class Confirmed implements ParameterizedRule
{
    use FormatsParameters;

    protected ?string $confirmationField = null;

    /**
     * Specify the confirmation field.
     */
    public function customField(string $confirmationField): static
    {
        $this->confirmationField = $confirmationField;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return $this->confirmationField === null ? ['confirmed'] : ['confirmed', $this->confirmationField];
    }
}
