<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Validation\Contracts\Rule;
use \Webkernel\Validation\Contracts\ValidatorAwareRule;
use \Webkernel\Accounts\Gate;

class Can implements Rule, ValidatorAwareRule
{
    /**
     * The ability to check.
     *
     * @var string
     */
    protected $ability;

    /**
     * The arguments to pass to the authorization check.
     *
     * @var array
     */
    protected $arguments;

    /**
     * The current validator instance.
     *
     * @var \Webkernel\Validation\Validator
     */
    protected $validator;

    /**
     * Constructor.
     *
     * @param  string  $ability
     * @param  array  $arguments
     */
    public function __construct($ability, array $arguments = [])
    {
        $this->ability = $ability;
        $this->arguments = $arguments;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $arguments = $this->arguments;

        $model = array_shift($arguments);

        return Gate::allows($this->ability, array_filter([$model, ...$arguments, $value]));
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.can');

        return $message === 'validation.can'
            ? ['The :attribute field contains an unauthorized value.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \Webkernel\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
