<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation;

class Precognition
{
    /**
     * Get the "after" validation hook that can be used for precognition requests.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @return \Closure
     */
    public static function afterValidationHook($request)
    {
        return function ($validator) use ($request) {
            if ($validator->messages()->isEmpty() && $request->headers->has('Precognition-Validate-Only')) {
                abort(204, headers: ['Precognition-Success' => 'true']);
            }
        };
    }
}
