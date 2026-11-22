<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts\Access\Events;

class GateEvaluated
{
    /**
     * The authenticatable model.
     *
     * @var \Webkernel\Accounts\Contracts\Authenticatable|null
     */
    public $user;

    /**
     * The ability being evaluated.
     *
     * @var string
     */
    public $ability;

    /**
     * The result of the evaluation.
     *
     * @var bool|null
     */
    public $result;

    /**
     * The arguments given during evaluation.
     *
     * @var array
     */
    public $arguments;

    /**
     * Create a new event instance.
     *
     * @param  \Webkernel\Accounts\Contracts\Authenticatable|null  $user
     * @param  string  $ability
     * @param  bool|null  $result
     * @param  array  $arguments
     */
    public function __construct($user, $ability, $result, $arguments)
    {
        $this->user = $user;
        $this->ability = $ability;
        $this->result = $result;
        $this->arguments = $arguments;
    }
}
