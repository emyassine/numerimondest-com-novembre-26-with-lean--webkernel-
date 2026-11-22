<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts\Access;

trait HandlesAuthorization
{
    /**
     * Create a new access response.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Webkernel\Accounts\Access\Response
     */
    protected function allow($message = null, $code = null)
    {
        return Response::allow($message, $code);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Webkernel\Accounts\Access\Response
     */
    protected function deny($message = null, $code = null)
    {
        return Response::deny($message, $code);
    }

    /**
     * Deny with a HTTP status code.
     *
     * @param  int  $status
     * @param  string|null  $message
     * @param  int|null  $code
     * @return \Webkernel\Accounts\Access\Response
     */
    public function denyWithStatus($status, $message = null, $code = null)
    {
        return Response::denyWithStatus($status, $message, $code);
    }

    /**
     * Deny with a 404 HTTP status code.
     *
     * @param  string|null  $message
     * @param  int|null  $code
     * @return \Webkernel\Accounts\Access\Response
     */
    public function denyAsNotFound($message = null, $code = null)
    {
        return Response::denyWithStatus(404, $message, $code);
    }
}
