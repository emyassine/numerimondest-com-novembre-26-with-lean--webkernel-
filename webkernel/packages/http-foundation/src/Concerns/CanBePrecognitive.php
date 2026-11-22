<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Concerns;

use \Webkernel\Utilities\Collection;

trait CanBePrecognitive
{
    /**
     * Filter the given array of rules into an array of rules that are included in precognitive headers.
     *
     * @param  array  $rules
     * @return array
     */
    public function filterPrecognitiveRules($rules)
    {
        if (! $this->headers->has('Precognition-Validate-Only')) {
            return $rules;
        }

        $validateOnly = explode(',', $this->header('Precognition-Validate-Only'));

        return (new Collection($rules))
            ->filter(fn ($rule, $attribute) => $this->shouldValidatePrecognitiveAttribute($attribute, $validateOnly))
            ->all();
    }

    /**
     * Determine if the given attribute should be validated.
     *
     * @param  string  $attribute
     * @param  array  $validateOnly
     * @return bool
     */
    protected function shouldValidatePrecognitiveAttribute($attribute, $validateOnly)
    {
        foreach ($validateOnly as $pattern) {
            $regex = '/^'.str_replace('\*', '[^.]+', preg_quote($pattern, '/')).'$/';

            if (preg_match($regex, $attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the request is attempting to be precognitive.
     *
     * @return bool
     */
    public function isAttemptingPrecognition()
    {
        return $this->header('Precognition') === 'true';
    }

    /**
     * Determine if the request is precognitive.
     *
     * @return bool
     */
    public function isPrecognitive()
    {
        return $this->attributes->get('precognitive', false);
    }
}
