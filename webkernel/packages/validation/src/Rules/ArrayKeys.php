<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation\Rules;

use \Webkernel\Utilities\Contracts\Arrayable;
use Stringable;

use function \Webkernel\Utilities\enum_value;

class ArrayKeys implements Stringable
{
    /**
     * The accepted keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * Create a new array keys rule instance.
     *
     * @param  \Webkernel\Utilities\Contracts\Arrayable|array  $keys
     */
    public function __construct($keys)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $this->keys = is_array($keys) ? $keys : func_get_args();
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        $keys = array_map(
            static fn ($key) => enum_value($key),
            $this->keys,
        );

        return 'array_keys:'.implode(',', $keys);
    }
}
