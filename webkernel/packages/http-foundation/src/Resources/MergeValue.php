<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Resources;

use \Webkernel\Utilities\Collection;
use JsonSerializable;

class MergeValue
{
    /**
     * The data to be merged.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new merge value instance.
     *
     * @param  \Webkernel\Utilities\Collection|\JsonSerializable|array  $data
     */
    public function __construct($data)
    {
        $this->data = match (true) {
            $data instanceof Collection => $data->all(),
            $data instanceof JsonSerializable => $data->jsonSerialize(),
            default => $data,
        };
    }
}
