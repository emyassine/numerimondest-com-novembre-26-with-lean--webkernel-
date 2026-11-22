<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules\Concerns;

trait FormatsParameters
{
    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        $parameters = $this->toArray();
        $rule = array_shift($parameters);

        return $rule.($parameters ? ':'.$this->formatParameters($parameters) : '');
    }

    /**
     * Format parameters for a legacy validation rule string.
     */
    protected function formatParameters(array $parameters): string
    {
        return implode(',', array_map(static function ($parameter) {
            $parameter = (string) $parameter;

            return str_contains($parameter, ',') || str_contains($parameter, '"')
                ? '"'.str_replace('"', '""', $parameter).'"'
                : $parameter;
        }, $parameters));
    }
}
