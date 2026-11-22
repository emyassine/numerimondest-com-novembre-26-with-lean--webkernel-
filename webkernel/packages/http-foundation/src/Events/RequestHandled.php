<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Events;

class RequestHandled
{
    /**
     * The request instance.
     *
     * @var \Webkernel\HttpFoundation\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \Webkernel\HttpFoundation\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @param  \Webkernel\HttpFoundation\Response  $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
